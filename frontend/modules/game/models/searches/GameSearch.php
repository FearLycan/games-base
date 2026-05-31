<?php

namespace frontend\modules\game\models\searches;

use common\models\GameOffer;
use common\models\GameSale;
use frontend\modules\game\models\Game;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class GameSearch extends Game
{
    /** How long the total-count for a given filter combination is cached. */
    private const int COUNT_CACHE_TTL = YII_DEBUG ? 1 : 1800;

    public const string SORT_RELEASE = 'release';
    public const string SORT_OLDEST = 'oldest';
    public const string SORT_NAME = 'name';
    public const string SORT_REVIEWS = 'reviews';
    public const string SORT_PRICE_ASC = 'price-asc';
    public const string SORT_PRICE_DESC = 'price-desc';
    public const string SORT_META = 'meta';

    public const array PRICE_BUCKETS = [
        'free'     => 'Free',
        'under-10' => 'Under $10',
        '10-30'    => '$10 – $30',
        '30-60'    => '$30 – $60',
        'premium'  => '$60+',
        'on-sale'  => 'On sale',
    ];

    public const array RELEASE_BUCKETS = [
        'this-year' => 'This year',
        'last-year' => 'Last year',
        '2-5-years' => '2 – 5 years',
        'older'     => 'Older',
    ];

    public const array REVIEW_BUCKETS = [
        'overwhelmingly-positive' => 'Overwhelmingly Positive',
        'very-positive'           => 'Very Positive',
        'mostly-positive'         => 'Mostly Positive',
        'mixed'                   => 'Mixed',
        'mostly-negative'         => 'Mostly Negative',
    ];

    public const array DECK_BUCKETS = [
        'verified'    => 'Verified',
        'playable'    => 'Playable',
        'unsupported' => 'Unsupported',
    ];

    public const array TYPE_BUCKETS = [
        'game' => 'Game',
        'dlc'  => 'DLC',
        'demo' => 'Demo',
    ];

    public $category_id;
    public $genre_id;
    public $sale;
    public $q;
    public $sort = self::SORT_RELEASE;

    public $price;        // string key from PRICE_BUCKETS
    public $release;      // string key from RELEASE_BUCKETS
    public $game_type;    // string key from TYPE_BUCKETS
    public $platform;     // array of windows|mac|linux
    public $age_adult;    // '1' to filter adult-only
    public $deck;         // string key from DECK_BUCKETS
    public $reviews;      // string key from REVIEW_BUCKETS
    public $meta_min;     // integer 0-100

    public $genre_ids;     // int[]
    public $tag_ids;       // int[]
    public $category_ids;  // int[]
    public $developer_ids; // int[]
    public $publisher_ids; // int[]

    public function rules(): array
    {
        return [
            [['category_id', 'genre_id', 'sale', 'meta_min'], 'integer'],
            [['q', 'sort', 'price', 'release', 'game_type', 'age_adult', 'deck', 'reviews'], 'string'],
            [['platform'], 'each', 'rule' => ['in', 'range' => ['windows', 'mac', 'linux']]],
            [['genre_ids', 'tag_ids', 'category_ids', 'developer_ids', 'publisher_ids'], 'each', 'rule' => ['integer']],
            ['sort', 'in', 'range' => [
                self::SORT_RELEASE, self::SORT_OLDEST, self::SORT_NAME,
                self::SORT_REVIEWS, self::SORT_PRICE_ASC, self::SORT_PRICE_DESC,
                self::SORT_META,
            ]],
            ['price', 'in', 'range' => array_keys(self::PRICE_BUCKETS)],
            ['release', 'in', 'range' => array_keys(self::RELEASE_BUCKETS)],
            ['game_type', 'in', 'range' => array_keys(self::TYPE_BUCKETS)],
            ['deck', 'in', 'range' => array_keys(self::DECK_BUCKETS)],
            ['reviews', 'in', 'range' => array_keys(self::REVIEW_BUCKETS)],
            ['meta_min', 'integer', 'min' => 0, 'max' => 100],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied.
     */
    public function search(array $params): ActiveDataProvider
    {
        $this->load($params);
        if (!$this->validate()) {
            $this->sort = self::SORT_RELEASE;
        }

        $query = Game::find()
            ->alias('game')
            ->andWhere(['game.status' => self::STATUS_ACTIVE])
            // Eager-load everything the cards read (genre + cheapest offer with
            // its prices/store) so a full page of cards costs a handful of
            // queries instead of one per game. See Game::getDisplayPrice().
            ->with([
                'genres',
                'gameOffers' => static function ($q): void {
                    $q->andWhere(['game_offer.status' => GameOffer::STATUS_ACTIVE])
                        ->orderBy(['game_offer.order' => SORT_ASC])
                        ->with(['store', 'prices']);
                },
            ]);

        // game_type semantics:
        //   null  → no param in URL    → default to TYPE_GAME (sensible landing default)
        //   ''    → "Any" chip checked → no filter, show all types
        //   other → explicit pick      → filter to that type
        if ($this->game_type === null) {
            $query->andWhere(['game.type' => self::TYPE_GAME]);
        } else if ($this->game_type !== '') {
            $query->andWhere(['game.type' => $this->game_type]);
        }

        if ($this->category_id) {
            $query->innerJoin('{{%game_category}} gc', 'gc.game_id = game.id')
                ->andWhere(['gc.category_id' => $this->category_id]);
        }

        if ($this->genre_id) {
            $query->innerJoin('{{%game_genre}} gg', 'gg.game_id = game.id')
                ->andWhere(['gg.genre_id' => $this->genre_id]);
        }

        if ($this->q !== null && $this->q !== '') {
            $query->andWhere(['like', 'game.title', $this->q]);
        }

        $this->applyPrice($query);
        $this->applyRelease($query);
        $this->applyPlatform($query);
        $this->applyAge($query);
        $this->applyDeck($query);
        $this->applyReviews($query);
        $this->applyMeta($query);
        $this->applyRelationFilter($query, $this->genre_ids, '{{%game_genre}}', 'genre_id');
        $this->applyRelationFilter($query, $this->tag_ids, '{{%game_tag}}', 'tag_id');
        $this->applyRelationFilter($query, $this->category_ids, '{{%game_category}}', 'category_id');
        $this->applyRelationFilter($query, $this->developer_ids, '{{%game_developer}}', 'developer_id');
        $this->applyRelationFilter($query, $this->publisher_ids, '{{%game_publisher}}', 'publisher_id');
        $this->applySort($query);

        $dataProvider = new ActiveDataProvider([
            'query'      => $query,
            'pagination' => ['pageSize' => 24],
        ]);

        // Cache the total-count for this filter combination.
        $relIds = static fn($v): string => is_array($v) ? implode(',', $v) : (string)$v;
        $countKey = ['game-search.count',
                     $this->category_id, $this->genre_id, $this->sale, $this->q,
                     $this->price, $this->release, $this->game_type,
                     is_array($this->platform) ? implode(',', $this->platform) : null,
                     $this->age_adult, $this->deck, $this->reviews, $this->meta_min,
                     $relIds($this->genre_ids), $relIds($this->tag_ids), $relIds($this->category_ids),
                     $relIds($this->developer_ids), $relIds($this->publisher_ids),
        ];
        $dataProvider->totalCount = (int)Yii::$app->cache->getOrSet(
            $countKey,
            static fn(): int => (int)(clone $query)->limit(-1)->offset(-1)->count(),
            self::COUNT_CACHE_TTL,
        );

        return $dataProvider;
    }

    private function applyPrice($query): void
    {
        match ($this->price) {
            'free' => $query->andWhere(['game.is_free' => 1]),
            'under-10' => $query->andWhere(['between', 'game.steam_price_final', 1, 999]),
            '10-30' => $query->andWhere(['between', 'game.steam_price_final', 1000, 2999]),
            '30-60' => $query->andWhere(['between', 'game.steam_price_final', 3000, 5999]),
            'premium' => $query->andWhere(['>=', 'game.steam_price_final', 6000]),
            'on-sale' => $query->andWhere('game.steam_price_final < game.steam_price_initial AND game.steam_price_initial > 0'),
            default => null,
        };
    }

    private function applyRelease($query): void
    {
        $thisYearStart = date('Y') . '-01-01';
        $lastYearStart = (date('Y') - 1) . '-01-01';
        $fiveAgo = (date('Y') - 5) . '-01-01';

        match ($this->release) {
            'this-year' => $query->andWhere(['>=', 'game.release_date', $thisYearStart]),
            'last-year' => $query->andWhere(['between', 'game.release_date', $lastYearStart, $thisYearStart]),
            '2-5-years' => $query->andWhere(['between', 'game.release_date', $fiveAgo, $lastYearStart]),
            'older' => $query->andWhere(['<', 'game.release_date', $fiveAgo]),
            default => null,
        };
    }

    private function applyPlatform($query): void
    {
        if (empty($this->platform)) {
            return;
        }
        $platforms = is_array($this->platform) ? $this->platform : [$this->platform];

        $query->andWhere(['game.id' => (new \yii\db\Query())
            ->select('game_id')
            ->from('{{%platform}}')
            ->where(['name' => $platforms, 'available' => 1])
            ->groupBy('game_id')
            ->having(['=', 'COUNT(DISTINCT name)', count($platforms)]),
        ]);
    }

    private function applyAge($query): void
    {
        if ($this->age_adult === '1') {
            $query->andWhere(['game.required_age' => 1]);
        }
    }

    private function applyDeck($query): void
    {
        match ($this->deck) {
            'verified' => $query->andWhere(['game.steam_deck' => self::STEAM_DECK_VERIFIED]),
            'playable' => $query->andWhere(['game.steam_deck' => self::STEAM_DECK_PLAYABLE]),
            'unsupported' => $query->andWhere(['game.steam_deck' => self::STEAM_DECK_UNSUPPORTED]),
            default => null,
        };
    }

    private function applyReviews($query): void
    {
        if (!$this->reviews) {
            return;
        }

        $label = self::REVIEW_BUCKETS[$this->reviews] ?? null;
        if ($label === null) {
            return;
        }

        $query->andWhere(['game.id' => (new \yii\db\Query())
            ->select('game_id')
            ->from('{{%review}}')
            ->where(['description' => $label]),
        ]);
    }

    private function applyMeta($query): void
    {
        if ((int)$this->meta_min <= 0) {
            return;
        }

        $query->andWhere(['game.id' => (new \yii\db\Query())
            ->select('game_id')
            ->from('{{%metacritic}}')
            ->where(['>=', 'score', (int)$this->meta_min]),
        ]);
    }

    /**
     * AND filter: only games linked to ALL provided ids via the given pivot table.
     */
    private function applyRelationFilter($query, $ids, string $pivotTable, string $foreignKey): void
    {
        if (empty($ids)) {
            return;
        }
        $ids = is_array($ids) ? array_filter(array_map('intval', $ids)) : [(int)$ids];
        if (!$ids) {
            return;
        }

        $query->andWhere(['game.id' => (new \yii\db\Query())
            ->select('game_id')
            ->from($pivotTable)
            ->where([$foreignKey => $ids])
            ->groupBy('game_id')
            ->having(['=', "COUNT(DISTINCT $foreignKey)", count($ids)]),
        ]);
    }

    public function activeFilterCount(): int
    {
        $count = 0;
        if ($this->price) $count++;
        if ($this->release) $count++;
        if ($this->game_type) $count++;
        if (!empty($this->platform)) $count++;
        if ($this->age_adult) $count++;
        if ($this->deck) $count++;
        if ($this->reviews) $count++;
        if ($this->meta_min) $count++;
        if (!empty($this->genre_ids)) $count++;
        if (!empty($this->tag_ids)) $count++;
        if (!empty($this->category_ids)) $count++;
        if (!empty($this->developer_ids)) $count++;
        if (!empty($this->publisher_ids)) $count++;
        return $count;
    }

    /**
     * @param int[]|null $ids
     * @return array<int,array{id:int,text:string}> id+text pairs for selected items (rendered as initial Select2 options)
     */
    public function preloadOptions(string $modelClass, ?array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $rows = $modelClass::find()
            ->select(['id', 'name'])
            ->where(['id' => array_map('intval', $ids)])
            ->asArray()
            ->all();

        return array_map(static fn($r) => ['id' => (int)$r['id'], 'text' => $r['name']], $rows);
    }

    private function applySort($query): void
    {
        if ($this->sale === GameSale::TYPE_BESTSELLERS) {
            $query->innerJoin('{{%game_sale}} gs', 'gs.game_id = game.id')
                ->andWhere(['gs.type' => GameSale::TYPE_BESTSELLERS])
                ->orderBy(['gs.order' => SORT_ASC]);
            return;
        }

        match ($this->sort) {
            self::SORT_OLDEST => $query->orderBy(['game.release_date' => SORT_ASC]),
            self::SORT_NAME => $query->orderBy(['game.title' => SORT_ASC]),
            self::SORT_REVIEWS => $query
                ->leftJoin('{{%review}} r', 'r.game_id = game.id')
                ->andWhere(['>', 'r.total_reviews', 50])
                ->orderBy(['r.total_positive' => SORT_DESC]),
            self::SORT_PRICE_ASC => $query->orderBy(['game.steam_price_final' => SORT_ASC]),
            self::SORT_PRICE_DESC => $query->orderBy(['game.steam_price_final' => SORT_DESC]),
            self::SORT_META => $query
                ->leftJoin('{{%metacritic}} m', 'm.game_id = game.id')
                ->andWhere(['>', 'm.score', 0])
                ->orderBy(['m.score' => SORT_DESC]),
            default => $query->orderBy(['game.release_date' => SORT_DESC]),
        };
    }

    public function formName(): string
    {
        return '';
    }
}
