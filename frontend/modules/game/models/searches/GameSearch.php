<?php

namespace frontend\modules\game\models\searches;

use common\models\GameSale;
use frontend\modules\game\models\Game;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class GameSearch extends Game
{
    /** How long the total-count for a given filter combination is cached. */
    private const int COUNT_CACHE_TTL = 1800;

    public $category_id;
    public $genre_id;
    public $sale;

    public function rules(): array
    {
        return [];
    }

    public function scenarios(): array
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied.
     */
    public function search(array $params): ActiveDataProvider
    {
        $query = Game::find()
            ->alias('game')
            ->andWhere([
                'game.status' => self::STATUS_ACTIVE,
                'game.type'   => self::TYPE_GAME,
            ]);

        if ($this->category_id) {
            $query->innerJoin('{{%game_category}} gc', 'gc.game_id = game.id')
                ->andWhere(['gc.category_id' => $this->category_id]);
        }

        if ($this->genre_id) {
            $query->innerJoin('{{%game_genre}} gg', 'gg.game_id = game.id')
                ->andWhere(['gg.genre_id' => $this->genre_id]);
        }

        if ($this->sale === GameSale::TYPE_BESTSELLERS) {
            $query->innerJoin('{{%game_sale}} gs', 'gs.game_id = game.id')
                ->andWhere(['gs.type' => GameSale::TYPE_BESTSELLERS])
                ->orderBy(['gs.order' => SORT_ASC]);
        }

        $dataProvider = new ActiveDataProvider(['query' => $query]);

        // Cache the total-count for this filter combination. Pagination requires
        // a COUNT(*) which is the expensive part of a category listing
        // (50k+ rows). The result depends only on the filter params, so a
        // simple key built from them is sufficient.
        $countKey = ['game-search.count', $this->category_id, $this->genre_id, $this->sale];
        $dataProvider->totalCount = (int)Yii::$app->cache->getOrSet(
            $countKey,
            static fn(): int => (int)(clone $query)->limit(-1)->offset(-1)->count(),
            self::COUNT_CACHE_TTL,
        );

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        return $dataProvider;
    }
}
