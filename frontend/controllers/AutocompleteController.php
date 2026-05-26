<?php

namespace frontend\controllers;

use common\components\AccessControl;
use common\models\Category;
use common\models\Developer;
use common\models\Game;
use common\models\GameImage;
use common\models\Genre;
use common\models\Publisher;
use frontend\components\Controller;
use Yii;
use yii\caching\Cache;
use yii\helpers\Url;
use yii\web\Response;

class AutocompleteController extends Controller
{
    private const int MIN_LENGTH = 2;
    private const int LIMIT_GAMES = 6;
    private const int LIMIT_OTHER = 4;
    private const int CACHE_TTL = 1800;

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
                        'actions' => ['search'],
                        'roles'   => ['?', '@'],
                    ],
                ],
            ],
        ];
    }

    public function actionSearch($q = '')
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $query = trim((string)$q);

        if (mb_strlen($query) < self::MIN_LENGTH) {
            return [
                'query'   => $query,
                'total'   => 0,
                'groups'  => [],
            ];
        }

        $cacheKey = 'autocomplete:' . md5(mb_strtolower($query));

        return $this->cache->getOrSet($cacheKey, function () use ($query) {
            $games = $this->searchGames($query);
            $genres = $this->searchByName(Genre::class, $query, self::LIMIT_OTHER, 'list');
            $categories = $this->searchByName(Category::class, $query, self::LIMIT_OTHER, 'list');
            $publishers = $this->searchByName(Publisher::class, $query, self::LIMIT_OTHER, null);
            $developers = $this->searchByName(Developer::class, $query, self::LIMIT_OTHER, null);

            $groups = array_values(array_filter([
                $this->formatGroup('games', 'Games', $games),
                $this->formatGroup('genres', 'Genres', $genres),
                $this->formatGroup('categories', 'Categories', $categories),
                $this->formatGroup('publishers', 'Publishers', $publishers),
                $this->formatGroup('developers', 'Developers', $developers),
            ]));

            $total = array_sum(array_map(fn($g) => count($g['items']), $groups));

            return [
                'query'  => $query,
                'total'  => $total,
                'groups' => $groups,
            ];
        }, self::CACHE_TTL);
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
            ->orderBy(['review.total_reviews' => SORT_DESC, 'game.title' => SORT_ASC])
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
                'title'    => $row['title'],
                'subtitle' => $year ?: 'Steam',
                'image'    => $row['header_url'] ?? null,
                'url'      => Url::to([
                    '/game/game/view',
                    'id'   => $row['steam_appid'],
                    'slug' => $row['slug'],
                ]),
                'badge'    => $year,
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
