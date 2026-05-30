<?php

namespace backend\modules\admin\models\search;

use backend\models\Review;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class ReviewSearch extends Review
{
    use GameFilterTrait;

    public function rules(): array
    {
        return [
            [['id', 'total_positive', 'total_negative', 'total_reviews'], 'integer'],
            [['description', 'gameTitle'], 'safe'],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = Review::find()->joinWith('game');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'  => [
                'defaultOrder' => ['id' => SORT_DESC],
                'attributes'   => [
                    'id'             => ['asc' => ['review.id' => SORT_ASC], 'desc' => ['review.id' => SORT_DESC]],
                    'total_positive' => ['asc' => ['review.total_positive' => SORT_ASC], 'desc' => ['review.total_positive' => SORT_DESC]],
                    'total_negative' => ['asc' => ['review.total_negative' => SORT_ASC], 'desc' => ['review.total_negative' => SORT_DESC]],
                    'total_reviews'  => ['asc' => ['review.total_reviews' => SORT_ASC], 'desc' => ['review.total_reviews' => SORT_DESC]],
                    'gameTitle'      => ['asc' => ['game.title' => SORT_ASC], 'desc' => ['game.title' => SORT_DESC]],
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'review.id'             => $this->id,
            'review.total_positive' => $this->total_positive,
            'review.total_negative' => $this->total_negative,
            'review.total_reviews'  => $this->total_reviews,
        ]);

        $query->andFilterWhere(['like', 'review.description', $this->description]);
        $this->applyGameFilter($query);

        return $dataProvider;
    }
}
