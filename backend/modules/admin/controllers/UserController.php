<?php

namespace backend\modules\admin\controllers;

use backend\modules\admin\models\search\UserSearch;
use backend\modules\admin\models\UserForm;
use backend\models\User;
use Yii;
use yii\web\Response;

class UserController extends CrudController
{
    public string $modelClass = User::class;
    public string $searchModelClass = UserSearch::class;
    public string $modelLabel = 'User';
    public string $modelLabelPlural = 'Users';

    protected ?string $toggleAttribute = 'status';
    protected int $toggleOn = User::STATUS_ACTIVE;
    protected int $toggleOff = User::STATUS_INACTIVE;

    protected function statusFilterOptions(): array
    {
        return UserForm::statusOptions();
    }

    protected function gridColumns(): array
    {
        return [
            'id',
            'username',
            'email',
            [
                'attribute' => 'role',
                'value'     => static fn(User $model): string => UserForm::roleOptions()[$model->role] ?? (string)$model->role,
            ],
            $this->statusColumn(),
            $this->actionColumn(),
        ];
    }

    public function viewAttributes(): ?array
    {
        return [
            'id',
            'username',
            'email',
            [
                'attribute' => 'role',
                'value'     => static fn(User $model): ?string => UserForm::roleOptions()[$model->role] ?? null,
            ],
            [
                'attribute' => 'status',
                'value'     => static fn(User $model): ?string => UserForm::statusOptions()[$model->status] ?? null,
            ],
            'created_at:datetime',
            'updated_at:datetime',
        ];
    }

    /** Purpose-built user detail screen. */
    public function actionView(int $id): string
    {
        return $this->render('view', [
            'model'      => $this->findModel($id),
            'controller' => $this,
        ]);
    }

    public function actionCreate(): Response|string
    {
        $form = new UserForm();

        if ($form->load(Yii::$app->request->post()) && $form->save()) {
            Yii::$app->session->setFlash('success', 'User created.');
            return $this->redirect(['view', 'id' => $form->getUser()->id]);
        }

        return $this->render('form', [
            'form'       => $form,
            'controller' => $this,
        ]);
    }

    public function actionUpdate(int $id): Response|string
    {
        $form = new UserForm($this->findModel($id));

        if ($form->load(Yii::$app->request->post()) && $form->save()) {
            Yii::$app->session->setFlash('success', 'User updated.');
            return $this->redirect(['view', 'id' => $id]);
        }

        return $this->render('form', [
            'form'       => $form,
            'controller' => $this,
        ]);
    }
}
