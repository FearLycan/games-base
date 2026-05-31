<?php

namespace frontend\controllers;

use common\components\AccessControl;
use common\models\Category;
use common\models\Developer;
use common\models\Game;
use common\models\GameImage;
use common\models\Genre;
use common\models\Publisher;
use common\models\Tag;
use frontend\components\Controller;
use Yii;
use yii\caching\Cache;
use yii\helpers\Url;
use yii\web\Response;

class AutocompleteController extends Controller
{
    private const int MIN_LENGTH = 3;
    private const int LIMIT_GAMES = 6;
    private const int LIMIT_OTHER = 4;
    private const int CACHE_TTL = 1800;
    private const int TRENDING_LIMIT = 5;
    private const int TRENDING_CACHE_TTL = 300;

    private Cache $cache;

    public function __construct($id, $module, $config = [])
    {
        $this->cache = Yii::$app->cache;
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow'   => true,
                        'actions' => ['search', 'select2', 'trending', 'track'],
                        'roles'   => ['?', '@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Select2-compatible endpoint: returns `{results: [{id, text}], pagination: {more: bool}}`.
     * Backs the multi-select filters on /games (genre, tag, category, developer, publisher).
     */
    public function actionSelect2(string $type, string $q = '', int $page = 1)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $modelClass = match ($type) {
            'genre' => Genre::class,
            'tag' => Tag::class,
            'category' => Category::class,
            'developer' => Developer::class,
            'publisher' => Publisher::class,
            default => null,
        };

        if ($modelClass === null) {
            return ['results' => [], 'pagination' => ['more' => false]];
        }

        $pageSize = 30;
        $offset = max(0, ($page - 1) * $pageSize);

        $query = $modelClass::find()
            ->select(['id', 'name'])
            ->orderBy(['name' => SORT_ASC])
            ->where('name != ""')
            ->limit($pageSize + 1)
            ->offset($offset)
            ->asArray();

        if ($q !== '') {
            $query->where(['like', 'name', $q]);
        }

        $rows = $query->all();
        $hasMore = count($rows) > $pageSize;
        $rows = array_slice($rows, 0, $pageSize);

        return [
            'results'    => array_map(static fn($r) => ['id' => (int)$r['id'], 'text' => $r['name']], $rows),
            'pagination' => ['more' => $hasMore],
        ];
    }

    public function actionSearch($q = '')
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $query = trim((string)$q);

        if (mb_strlen($query) < self::MIN_LENGTH) {
            return [
                'query'  => $query,
                'total'  => 0,
                'groups' => [],
            ];
        }

        $cacheKey = 'autocomplete:' . md5(mb_strtolower($query));

        return $this->cache->getOrSet($cacheKey, function () use ($query) {
            $games = $this->searchGames($query);
            $genres = $this->searchByName(Genre::class, $query, self::LIMIT_OTHER, 'list');
            $categories = $this->searchByName(Category::class, $query, self::LIMIT_OTHER, 'list');
            [$companies, $developers, $publishers] = $this->searchCompaniesAndRoles($query);

            $groups = array_values(array_filter([
                $this->formatGroup('games', 'Games', $games),
                $this->formatGroup('genres', 'Genres', $genres),
                $this->formatGroup('categories', 'Categories', $categories),
                $this->formatGroup('companies', 'Companies', $companies),
                $this->formatGroup('developers', 'Developers', $developers),
                $this->formatGroup('publishers', 'Publishers', $publishers),
            ]));

            $total = array_sum(array_map(fn($g) => count($g['items']), $groups));

            return [
                'query'  => $query,
                'total'  => $total,
                'groups' => $groups,
            ];
        }, self::CACHE_TTL);
    }

    /**
     * Bumps the daily click counter for a game. Called via sendBeacon when
     * a user clicks a search result. Accepts steam_appid (stable, external)
     * rather than internal game.id so the JS payload doesn't need it injected.
     */
    public function actionTrack()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            Yii::$app->response->statusCode = 405;
            return ['ok' => false];
        }

        $steamAppid = (int)Yii::$app->request->post('steam_appid');
        if ($steamAppid <= 0) {
            Yii::$app->response->statusCode = 400;
            return ['ok' => false];
        }

        $gameId = (int)Game::find()
            ->select('id')
            ->where(['steam_appid' => $steamAppid, 'status' => Game::STATUS_ACTIVE])
            ->scalar();

        if (!$gameId) {
            Yii::$app->response->statusCode = 404;
            return ['ok' => false];
        }

        Yii::$app->db->createCommand(
            'INSERT INTO {{%game_search_stat}} (game_id, day, count)
             VALUES (:gid, CURRENT_DATE, 1)
             ON DUPLICATE KEY UPDATE count = count + 1',
            [':gid' => $gameId]
        )->execute();

        Yii::$app->response->statusCode = 204;
        return null;
    }

    /**
     * Top-N games by clicks for today. Falls back to no results if nobody
     * has clicked anything today yet (acceptable — modal idle state covers it).
     */
    public function actionTrending()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $today = date('Y-m-d');
        $cacheKey = 'autocomplete:trending:' . $today;

        return $this->cache->getOrSet($cacheKey, function () use ($today) {
            $stats = (new \yii\db\Query())
                ->select(['game_id', 'count'])
                ->from('{{%game_search_stat}}')
                ->where(['day' => $today])
                ->orderBy(['count' => SORT_DESC])
                ->limit(self::TRENDING_LIMIT)
                ->all();

            if (!$stats) {
                return ['groups' => []];
            }

            $gameIds = array_column($stats, 'game_id');
            $rows = Game::find()
                ->select([
                    'game.id',
                    'game.steam_appid',
                    'game.title',
                    'game.slug',
                    'game.release_date',
                    'header_url' => GameImage::find()
                        ->select('url')
                        ->where("game_id = game.id AND type = 'header' AND status = " . GameImage::STATUS_ACTIVE)
                        ->limit(1),
                ])
                ->alias('game')
                ->where(['game.id' => $gameIds, 'game.status' => Game::STATUS_ACTIVE])
                ->indexBy('id')
                ->asArray()
                ->all();

            $items = [];
            foreach ($stats as $stat) {
                $row = $rows[$stat['game_id']] ?? null;
                if (!$row) {
                    continue;
                }

                $year = '';
                if (!empty($row['release_date'])) {
                    $ts = strtotime($row['release_date']);
                    if ($ts) {
                        $year = date('Y', $ts);
                    }
                }

                $items[] = [
                    'title'       => $row['title'],
                    'subtitle'    => $year ?: 'Steam',
                    'image'       => $row['header_url'] ?? null,
                    'url'         => Url::to(['/game/game/view', 'id' => $row['steam_appid'], 'slug' => $row['slug'],]),
                    'badge'       => '↑ ' . number_format((int)$stat['count']),
                    'steam_appid' => (int)$row['steam_appid'],
                ];
            }

            return [
                'groups' => $items ? [
                    [
                        'key'   => 'trending',
                        'label' => 'Trending today',
                        'items' => $items,
                    ],
                ] : [],
            ];
        }, self::TRENDING_CACHE_TTL);
    }

    private function searchGames(string $query): array
    {
        $rows = Game::find()
            ->select([
                'game.id',
                'game.steam_appid',
                'game.title',
                'game.slug',
                'game.release_date',
                'header_url' => GameImage::find()
                    ->select('url')
                    ->where("game_id = game.id AND type = 'header' AND status = " . GameImage::STATUS_ACTIVE)
                    ->limit(1),
            ])
            ->alias('game')
            ->onlyWithTitle($query)
            ->andWhere(['game.status' => Game::STATUS_ACTIVE])
            ->andWhere(['game.type' => Game::TYPE_GAME])
            ->joinWith(['review'], false)
            ->orderBy(['review.total_reviews' => SORT_DESC, 'game.title' => SORT_ASC, 'game.release_date' => SORT_DESC])
            ->limit(self::LIMIT_GAMES)
            ->asArray()
            ->all();

        return array_map(function ($row) {
            $year = '';
            if (!empty($row['release_date'])) {
                $ts = strtotime($row['release_date']);
                if ($ts) {
                    $year = date('Y', $ts);
                }
            }

            return [
                'title'       => $row['title'],
                'subtitle'    => $year ?: 'Steam',
                'image'       => $row['header_url'] ?? null,
                'url'         => Url::to([
                    '/game/game/view',
                    'id'   => $row['steam_appid'],
                    'slug' => $row['slug'],
                ]),
                'badge'       => $year,
                'steam_appid' => (int)$row['steam_appid'],
            ];
        }, $rows);
    }

    /**
     * @param class-string $modelClass
     */
    private function searchByName(string $modelClass, string $query, int $limit, ?string $listRoute): array
    {
        $rows = $modelClass::find()
            ->select(['id', 'name', 'slug'])
            ->where(['like', 'name', $query])
            ->orderBy(['name' => SORT_ASC])
            ->limit($limit)
            ->asArray()
            ->all();

        return array_map(function ($row) use ($listRoute) {
            $url = '#';
            if ($listRoute === 'list' && !empty($row['slug'])) {
                $url = Url::to(['/game/game/list', 'slug' => $row['slug']]);
            }

            return [
                'title'    => $row['name'],
                'subtitle' => null,
                'image'    => null,
                'url'      => $url,
                'badge'    => null,
            ];
        }, $rows);
    }

    /**
     * Splits company matches into three buckets:
     *   - companies: name exists in both developer and publisher → /company/<slug>
     *   - developers: developer only → /developer/<slug>
     *   - publishers: publisher only → /publisher/<slug>
     *
     * Each bucket is independently capped at LIMIT_OTHER and ordered alphabetically.
     *
     * @return array{0:array,1:array,2:array}
     */
    private function searchCompaniesAndRoles(string $query): array
    {
        $cap = self::LIMIT_OTHER * 2;

        $devRows = Developer::find()
            ->select(['name', 'slug'])
            ->where(['like', 'name', $query])
            ->orderBy(['name' => SORT_ASC])
            ->limit($cap)
            ->asArray()
            ->all();

        $pubRows = Publisher::find()
            ->select(['name', 'slug'])
            ->where(['like', 'name', $query])
            ->orderBy(['name' => SORT_ASC])
            ->limit($cap)
            ->asArray()
            ->all();

        $devByName = [];
        foreach ($devRows as $row) {
            if (!empty($row['name'])) {
                $devByName[$row['name']] = $row;
            }
        }
        $pubByName = [];
        foreach ($pubRows as $row) {
            if (!empty($row['name'])) {
                $pubByName[$row['name']] = $row;
            }
        }

        $companies = [];
        $developers = [];
        $publishers = [];

        foreach ($devByName as $name => $row) {
            if (empty($row['slug'])) continue;
            if (isset($pubByName[$name])) {
                $companies[$row['slug']] = ['name' => $name, 'slug' => $row['slug'], 'route' => '/company/company/view'];
            } else {
                $developers[$row['slug']] = ['name' => $name, 'slug' => $row['slug'], 'route' => '/developer/developer/view'];
            }
        }
        foreach ($pubByName as $name => $row) {
            if (empty($row['slug']) || isset($devByName[$name])) continue;
            $publishers[$row['slug']] = ['name' => $name, 'slug' => $row['slug'], 'route' => '/publisher/publisher/view'];
        }

        return [
            $this->formatRoleItems(array_slice($companies, 0, self::LIMIT_OTHER)),
            $this->formatRoleItems(array_slice($developers, 0, self::LIMIT_OTHER)),
            $this->formatRoleItems(array_slice($publishers, 0, self::LIMIT_OTHER)),
        ];
    }

    private function formatRoleItems(array $rows): array
    {
        return array_map(static fn($row) => [
            'title'    => $row['name'],
            'subtitle' => null,
            'image'    => null,
            'url'      => Url::to([$row['route'], 'slug' => $row['slug']]),
            'badge'    => null,
        ], array_values($rows));
    }

    private function formatGroup(string $key, string $label, array $items): ?array
    {
        if (empty($items)) {
            return null;
        }

        return [
            'key'   => $key,
            'label' => $label,
            'items' => $items,
        ];
    }
}
