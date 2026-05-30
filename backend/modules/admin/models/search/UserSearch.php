<?php

namespace backend\modules\admin\models\search;

use backend\models\User;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class UserSearch extends User
{
    public function rules(): array
    {
        return [
            [['id', 'status', 'role'], 'integer'],
            [['username', 'email'], 'safe'],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = User::find();

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
            'role'   => $this->role,
        ]);

        $query->andFilterWhere(['like', 'username', $this->username])
            ->andFilterWhere(['like', 'email', $this->email]);

        return $dataProvider;
    }
}
