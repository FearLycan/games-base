<?php

namespace common\components;

use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class Controller extends \yii\web\Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Throws ForbiddenHttpException.
     *
     * @param string|null $message
     * @throws ForbiddenHttpException
     */
    public function accessDenied($message = null)
    {
        if ($message === null) {
            $message = 'Sorry, you are not authorized to view this page';
        }

        throw new ForbiddenHttpException($message);
    }

    /**
     * Throws NotFoundHttpException.
     *
     * @param string|null $message
     * @throws NotFoundHttpException
     */
    public function notFound($message = null)
    {
        if ($message === null) {
            $message = 'The page you\'re looking for doesn\'t exist';
        }

        throw new NotFoundHttpException($message);
    }
}
