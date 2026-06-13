<?php

namespace backend\modules\admin\models\search;

use backend\models\IpAddress;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class IpAddressSearch extends IpAddress
{
    public function rules(): array
    {
        return [
            [['id', 'is_blocked', 'is_bot', 'last_status', 'error_count'], 'integer'],
            [['ip', 'note', 'country', 'last_path'], 'safe'],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = IpAddress::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            // Lead with the noisiest sources — that's who the admin is here to act on.
            'sort'  => [
                'defaultOrder' => ['error_count' => SORT_DESC],
                'attributes'   => ['id', 'ip', 'country', 'error_count', 'last_status', 'is_blocked', 'is_bot', 'last_seen_at'],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id'          => $this->id,
            'is_blocked'  => $this->is_blocked,
            'is_bot'      => $this->is_bot,
            'last_status' => $this->last_status,
        ]);

        $query->andFilterWhere(['like', 'ip', $this->ip])
            ->andFilterWhere(['like', 'note', $this->note])
            ->andFilterWhere(['like', 'country', $this->country]);

        return $dataProvider;
    }
}
