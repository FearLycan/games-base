<?php

use yii\data\ActiveDataProvider;
use yii\web\View;

/* @var $this View */
/* @var $dataProvider ActiveDataProvider */
/* @var $sort string */
/* @var $country string|null */
/* @var $countries string[] */

echo $this->render('@frontend/views/company/_index', [
    'dataProvider' => $dataProvider,
    'sort'         => $sort,
    'country'      => $country,
    'countries'    => $countries,
    'kind'         => 'publisher',
    'kindPlural'   => 'publishers',
    'pageTitle'    => 'Game publishers',
    'pageIntro'    => 'Publishers releasing games on Steam. Browse the catalogs, see the studios behind them, find your next favorite label.',
]);
