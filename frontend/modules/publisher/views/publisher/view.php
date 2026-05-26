<?php

use frontend\modules\publisher\models\Publisher;
use yii\data\ActiveDataProvider;
use yii\web\View;

/* @var $this View */
/* @var $model Publisher */
/* @var $dataProvider ActiveDataProvider */
/* @var $stats array */
/* @var $sort string */

echo $this->render('@frontend/views/company/_view', [
    'model'        => $model,
    'profile'      => $model->profile,
    'dataProvider' => $dataProvider,
    'stats'        => $stats,
    'sort'         => $sort,
    'kind'         => 'publisher',
    'kindLabel'    => 'Publisher',
    'gamesLabel'   => 'Games published',
]);
