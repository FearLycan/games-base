<?php

namespace frontend\modules\game\models\searches;

use frontend\modules\game\models\Game;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class GameSearch extends Game
{
    public $category_id;
    public $genre_id;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Game::find()
            ->alias('game');

        if ($this->category_id) {
            $query->joinWith(['categories']);
            $query->andWhere(['category.id' => $this->category_id]);
        }

        if ($this->genre_id) {
            $query->joinWith(['genres']);
            $query->andWhere(['genre.id' => $this->genre_id]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        return $dataProvider;
    }
}