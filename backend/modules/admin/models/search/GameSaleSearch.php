<?php

namespace backend\modules\admin\models\search;

use backend\models\GameSale;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class GameSaleSearch extends GameSale
{
    use GameFilterTrait;

    public function rules(): array
    {
        return [
            [['id', 'type', 'order'], 'integer'],
            [['gameTitle'], 'safe'],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = GameSale::find()->joinWith(['game']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            // Grouped by list, then by rank — how the rails actually read.
            'sort'  => [
                'defaultOrder' => ['type' => SORT_ASC, 'order' => SORT_ASC],
                'attributes'   => [
                    'id'        => ['asc' => ['game_sale.id' => SORT_ASC], 'desc' => ['game_sale.id' => SORT_DESC]],
                    'type'      => ['asc' => ['game_sale.type' => SORT_ASC], 'desc' => ['game_sale.type' => SORT_DESC]],
                    'order'     => ['asc' => ['game_sale.order' => SORT_ASC], 'desc' => ['game_sale.order' => SORT_DESC]],
                    'gameTitle' => ['asc' => ['game.title' => SORT_ASC], 'desc' => ['game.title' => SORT_DESC]],
                ],
            ],
            'pagination' => ['pageSize' => 50],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'game_sale.id'   => $this->id,
            'game_sale.type' => $this->type,
        ]);

        $this->applyGameFilter($query);

        return $dataProvider;
    }
}
