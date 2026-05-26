<?php

use yii\data\ActiveDataProvider;
use yii\web\View;

/* @var $this View */
/* @var $dataProvider ActiveDataProvider */
/* @var $sort string */
/* @var $country string|null */
/* @var $countries string[] */
/* @var $kind string */
/* @var $kindPlural string */

echo $this->render('@frontend/views/company/_index', [
    'dataProvider' => $dataProvider,
    'sort'         => $sort,
    'country'      => $country,
    'countries'    => $countries,
    'kind'         => 'developer',
    'kindPlural'   => 'developers',
    'pageTitle'    => 'Game developers',
    'pageIntro'    => 'Studios behind the games on Gamentator. Browse by country, sort by output, or jump straight into a catalog.',
]);
