<?php

namespace backend\modules\admin\models\search;

use backend\models\Game;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class GameSearch extends Game
{
    public function rules(): array
    {
        return [
            [['id', 'steam_appid', 'status', 'is_free'], 'integer'],
            [['title', 'type', 'release_date'], 'safe'],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = Game::find();

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
            'steam_appid' => $this->steam_appid,
            'status'      => $this->status,
            'is_free'     => $this->is_free,
            'type'        => $this->type,
        ]);

        $query->andFilterWhere(['like', 'title', $this->title])
            ->andFilterWhere(['like', 'release_date', $this->release_date]);

        return $dataProvider;
    }
}
