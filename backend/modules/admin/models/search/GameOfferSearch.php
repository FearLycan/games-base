<?php

namespace backend\modules\admin\models\search;

use backend\models\GameOffer;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class GameOfferSearch extends GameOffer
{
    use GameFilterTrait;

    public function rules(): array
    {
        return [
            [['id', 'status', 'store_id'], 'integer'],
            [['gameTitle', 'region', 'edition'], 'safe'],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = GameOffer::find()->joinWith(['game', 'store'])->with('prices');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'  => [
                'defaultOrder' => ['id' => SORT_DESC],
                'attributes'   => [
                    'id'        => ['asc' => ['game_offer.id' => SORT_ASC], 'desc' => ['game_offer.id' => SORT_DESC]],
                    'status'    => ['asc' => ['game_offer.status' => SORT_ASC], 'desc' => ['game_offer.status' => SORT_DESC]],
                    'region'    => ['asc' => ['game_offer.region' => SORT_ASC], 'desc' => ['game_offer.region' => SORT_DESC]],
                    'edition'   => ['asc' => ['game_offer.edition' => SORT_ASC], 'desc' => ['game_offer.edition' => SORT_DESC]],
                    'gameTitle' => ['asc' => ['game.title' => SORT_ASC], 'desc' => ['game.title' => SORT_DESC]],
                    'store_id'  => ['asc' => ['store.name' => SORT_ASC], 'desc' => ['store.name' => SORT_DESC]],
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'game_offer.id'       => $this->id,
            'game_offer.status'   => $this->status,
            'game_offer.store_id' => $this->store_id,
        ]);

        $query->andFilterWhere(['like', 'game_offer.region', $this->region])
            ->andFilterWhere(['like', 'game_offer.edition', $this->edition]);
        $this->applyGameFilter($query);

        return $dataProvider;
    }
}
