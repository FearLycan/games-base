<?php

namespace backend\modules\admin\models\search;

use backend\models\Category;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class CategorySearch extends Category
{
    /** Virtual filter: '1' = has an image, '0' = missing one. */
    public ?string $hasImage = null;

    public function rules(): array
    {
        return [
            [['id', 'status', 'games_count'], 'integer'],
            [['name', 'hasImage'], 'safe'],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = Category::find();

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

        if ($this->hasImage === '1') {
            $query->andWhere(['and', ['not', ['image' => null]], ['<>', 'image', '']]);
        } elseif ($this->hasImage === '0') {
            $query->andWhere(['or', ['image' => null], ['image' => '']]);
        }

        return $dataProvider;
    }
}
