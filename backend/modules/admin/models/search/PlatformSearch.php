<?php

namespace backend\modules\admin\models\search;

use backend\models\Platform;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class PlatformSearch extends Platform
{
    use GameFilterTrait;

    public function rules(): array
    {
        return [
            [['id', 'available'], 'integer'],
            [['name', 'gameTitle'], 'safe'],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = Platform::find()->joinWith('game');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'  => [
                'defaultOrder' => ['id' => SORT_DESC],
                'attributes'   => [
                    'id'        => ['asc' => ['platform.id' => SORT_ASC], 'desc' => ['platform.id' => SORT_DESC]],
                    'name'      => ['asc' => ['platform.name' => SORT_ASC], 'desc' => ['platform.name' => SORT_DESC]],
                    'available' => ['asc' => ['platform.available' => SORT_ASC], 'desc' => ['platform.available' => SORT_DESC]],
                    'gameTitle' => ['asc' => ['game.title' => SORT_ASC], 'desc' => ['game.title' => SORT_DESC]],
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'platform.id'        => $this->id,
            'platform.available' => $this->available,
        ]);

        $query->andFilterWhere(['like', 'platform.name', $this->name]);
        $this->applyGameFilter($query);

        return $dataProvider;
    }
}
