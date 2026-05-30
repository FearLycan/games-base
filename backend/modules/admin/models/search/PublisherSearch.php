<?php

namespace backend\modules\admin\models\search;

use backend\models\Publisher;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class PublisherSearch extends Publisher
{
    public function rules(): array
    {
        return [
            [['id', 'status', 'games_count', 'profile_id'], 'integer'],
            [['name'], 'safe'],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = Publisher::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'  => ['defaultOrder' => ['id' => SORT_DESC]],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id'          => $this->id,
            'status'      => $this->status,
            'games_count' => $this->games_count,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name]);

        return $dataProvider;
    }
}
