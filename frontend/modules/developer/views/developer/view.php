<?php

use frontend\modules\developer\models\Developer;
use yii\data\ActiveDataProvider;
use yii\web\View;

/* @var $this View */
/* @var $model Developer */
/* @var $dataProvider ActiveDataProvider */
/* @var $stats array */
/* @var $sort string */

echo $this->render('@frontend/views/company/_view', [
    'model'        => $model,
    'profile'      => $model->profile,
    'dataProvider' => $dataProvider,
    'stats'        => $stats,
    'sort'         => $sort,
    'kind'         => 'developer',
    'kindLabel'    => 'Developer',
    'gamesLabel'   => 'Games developed',
]);
