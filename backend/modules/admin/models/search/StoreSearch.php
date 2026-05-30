<?php

namespace backend\modules\admin\models\search;

use backend\models\Store;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class StoreSearch extends Store
{
    public function rules(): array
    {
        return [
            [['id', 'status', 'order'], 'integer'],
            [['name', 'website', 'slug'], 'safe'],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = Store::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'  => ['defaultOrder' => ['id' => SORT_DESC]],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id'     => $this->id,
            'status' => $this->status,
            'order'  => $this->order,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'website', $this->website]);

        return $dataProvider;
    }
}
