<?php

namespace frontend\modules\game\controllers;

use common\models\GameSale;
use frontend\components\Controller;
use frontend\modules\game\models\searches\GameSearch;

class SaleController extends Controller
{
    public function actionBestsellers()
    {
        $searchModel = new GameSearch();
        $searchModel->sale = GameSale::TYPE_BESTSELLERS;
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('bestsellers', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
}