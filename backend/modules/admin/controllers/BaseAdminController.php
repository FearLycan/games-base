<?php

namespace backend\modules\admin\controllers;

use backend\modules\admin\assets\AdminAsset;
use common\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;

/**
 * Base controller for the admin module. Centralises the ROLE_ADMIN access rule
 * and the POST-only verbs for the mutating AJAX actions, and registers the
 * module asset bundle so every admin page ships the toggle/delete glue.
 */
abstract class BaseAdminController extends Controller
{
    public function init(): void
    {
        parent::init();
        AdminAsset::register($this->view);
    }

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow'         => true,
                        'roles'         => ['@'],
                        'matchCallback' => static function (): bool {
                            $identity = Yii::$app->user->identity;
                            return $identity instanceof User
                                && (int)$identity->role === User::ROLE_ADMIN;
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class'   => VerbFilter::class,
                'actions' => [
                    'delete'        => ['POST'],
                    'toggle-status' => ['POST'],
                    'accept'        => ['POST'],
                    'reject'        => ['POST'],
                ],
            ],
        ];
    }
}
