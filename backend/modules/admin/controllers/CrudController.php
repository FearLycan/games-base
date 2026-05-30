<?php

namespace backend\modules\admin\controllers;

use backend\modules\admin\components\AdminHtml;
use Throwable;
use Yii;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;
use yii\grid\ActionColumn;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Generic CRUD controller shared by every model managed in the admin module.
 *
 * A concrete controller only declares the model/search classes, the toggle
 * config and {@see gridColumns()}; all action logic (list, view, create,
 * update, AJAX delete, AJAX status toggle) lives here. Index/view/create/update
 * render the shared views under views/crud/, while the create/update forms
 * resolve the per-controller `_form` partial.
 */
abstract class CrudController extends BaseAdminController
{
    /** @var class-string<ActiveRecord> */
    public string $modelClass;
    /** @var class-string<ActiveRecord> */
    public string $searchModelClass;

    /** Human label for headings/buttons, e.g. "Game". */
    public string $modelLabel = 'Item';
    /** Plural label for the list heading, e.g. "Games". */
    public string $modelLabelPlural = 'Items';

    /** Attribute flipped by the inline switch, or null to disable toggling. */
    protected ?string $toggleAttribute = 'status';
    /** Value representing the "on" state. */
    protected int $toggleOn = 1;
    /** Value representing the "off" state. */
    protected int $toggleOff = 0;

    /**
     * Data columns for the list, excluding the trailing status/action columns
     * (compose those via {@see statusColumn()} / {@see actionColumn()}).
     *
     * @return array<int, mixed>
     */
    abstract protected function gridColumns(): array;

    /**
     * Turns a plain `'id'` column into a narrow, muted one (it's reference data,
     * not the focus of the row). Applied to every list, so controllers can keep
     * declaring just `'id'`.
     *
     * @param array<int, mixed> $columns
     * @return array<int, mixed>
     */
    private function withCompactId(array $columns): array
    {
        foreach ($columns as &$column) {
            if ($column === 'id') {
                $column = [
                    'attribute'      => 'id',
                    'contentOptions' => ['class' => 'col-id'],
                    'headerOptions'  => ['class' => 'col-id'],
                    'filterOptions'  => ['class' => 'col-id'],
                ];
            }
        }
        unset($column);

        return $columns;
    }

    /**
     * Turns a plain primary-label column (`'name'` / `'username'`) into a link to
     * the row's view page — the same destination as the eye icon — so the most
     * scannable cell is also the quickest way in. Filtering and sorting are kept
     * by leaving the attribute bound.
     *
     * @param array<int, mixed> $columns
     * @return array<int, mixed>
     */
    private function withNameLinks(array $columns): array
    {
        $linkable = ['name', 'username'];

        foreach ($columns as &$column) {
            if (is_string($column) && in_array($column, $linkable, true)) {
                $attribute = $column;
                $column = [
                    'attribute' => $attribute,
                    'format'    => 'raw',
                    'value'     => static function (ActiveRecord $model) use ($attribute): string {
                        $label = (string)$model->getAttribute($attribute);
                        return Html::a(
                            Html::encode($label !== '' ? $label : '#' . $model->getPrimaryKey()),
                            Url::to(['view', 'id' => $model->getPrimaryKey()]),
                            ['class' => 'grid-name-link'],
                        );
                    },
                ];
            }
        }
        unset($column);

        return $columns;
    }

    public function actionIndex(): string
    {
        $searchModel = new $this->searchModelClass();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('@backend/modules/admin/views/crud/index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
            'columns'      => $this->withNameLinks($this->withCompactId($this->gridColumns())),
            'controller'   => $this,
        ]);
    }

    public function actionView(int $id): string
    {
        return $this->render('@backend/modules/admin/views/crud/view', [
            'model'      => $this->findModel($id),
            'controller' => $this,
        ]);
    }

    public function actionCreate(): Response|string
    {
        $model = new $this->modelClass();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', $this->modelLabel . ' created.');
            return $this->redirect(['view', 'id' => $model->getPrimaryKey()]);
        }

        return $this->render('@backend/modules/admin/views/crud/create', [
            'model'      => $model,
            'controller' => $this,
        ]);
    }

    public function actionUpdate(int $id): Response|string
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', $this->modelLabel . ' updated.');
            return $this->redirect(['view', 'id' => $model->getPrimaryKey()]);
        }

        return $this->render('@backend/modules/admin/views/crud/update', [
            'model'      => $model,
            'controller' => $this,
        ]);
    }

    /**
     * Deletes a row. Returns JSON for AJAX (the grid drops the row client-side);
     * falls back to a redirect for non-AJAX requests.
     */
    public function actionDelete(int $id): Response
    {
        $isAjax = Yii::$app->request->isAjax;

        try {
            $this->findModel($id)->delete();
            $message = $this->modelLabel . ' deleted.';
            $success = true;
        } catch (StaleObjectException | Throwable $e) {
            $message = 'Could not delete: the record may be referenced by other data.';
            $success = false;
        }

        if ($isAjax) {
            return $this->asJson(['success' => $success, 'message' => $message]);
        }

        Yii::$app->session->setFlash($success ? 'success' : 'error', $message);
        return $this->redirect(['index']);
    }

    /**
     * Flips the configured toggle attribute between its on/off values and
     * returns the resulting state as JSON.
     */
    public function actionToggleStatus(int $id): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if ($this->toggleAttribute === null) {
            return $this->asJson(['success' => false, 'message' => 'Toggling is not supported here.']);
        }

        $model = $this->findModel($id);
        $attribute = $this->toggleAttribute;

        $turnOn = (int)$model->getAttribute($attribute) !== $this->toggleOn;
        $model->setAttribute($attribute, $turnOn ? $this->toggleOn : $this->toggleOff);

        if (!$model->save(false, [$attribute, 'updated_at'])) {
            return $this->asJson(['success' => false, 'message' => 'Could not save the new status.']);
        }

        return $this->asJson([
            'success' => true,
            'active'  => $turnOn,
            'message' => 'Status updated.',
        ]);
    }

    /**
     * Options for the status column's filter dropdown. Defaults to the toggle's
     * on/off values; override for models with a richer status set.
     *
     * @return array<int, string>
     */
    protected function statusFilterOptions(): array
    {
        return [
            $this->toggleOn  => 'Active',
            $this->toggleOff => 'Inactive',
        ];
    }

    /**
     * Builds the inline status-switch column. Returns an empty marker the grid
     * ignores when toggling is disabled for the model.
     *
     * @return array<string, mixed>
     */
    protected function statusColumn(): array
    {
        $attribute = $this->toggleAttribute ?? 'status';
        $onValue = $this->toggleOn;

        return [
            'attribute' => $attribute,
            'format'    => 'raw',
            'filter'    => $this->statusFilterOptions(),
            'filterInputOptions' => ['class' => 'form-select form-select-sm'],
            'contentOptions' => ['class' => 'text-center col-status'],
            'headerOptions'  => ['class' => 'text-center col-status'],
            'filterOptions'  => ['class' => 'col-status'],
            'value'     => function (ActiveRecord $model) use ($attribute, $onValue): string {
                return AdminHtml::statusSwitch(
                    $model,
                    $attribute,
                    $onValue,
                    Url::to(['toggle-status', 'id' => $model->getPrimaryKey()]),
                );
            },
        ];
    }

    /**
     * Builds the actions column: view, update and an AJAX-driven delete.
     *
     * @return array<string, mixed>
     */
    protected function actionColumn(): array
    {
        return [
            'class'    => ActionColumn::class,
            'header'   => 'Actions',
            'template' => '{view} {update} {delete}',
            'contentOptions' => ['class' => 'action-column'],
            'headerOptions'  => ['class' => 'action-column'],
            'buttons'  => [
                'view' => static fn(string $url): string => Html::a(
                    '<i class="bi bi-eye"></i>',
                    $url,
                    ['class' => 'text-secondary me-2', 'title' => 'View'],
                ),
                'update' => static fn(string $url): string => Html::a(
                    '<i class="bi bi-pencil-square"></i>',
                    $url,
                    ['class' => 'text-primary me-2', 'title' => 'Edit'],
                ),
                'delete' => static fn(string $url): string => Html::a(
                    '<i class="bi bi-trash"></i>',
                    $url,
                    [
                        // Custom attribute (not data-confirm) so yii.js does not
                        // also bind this link — the admin.js handler owns it.
                        'class'             => 'text-danger js-ajax-delete',
                        'title'             => 'Delete',
                        'data-confirm-text' => 'Are you sure you want to delete this item?',
                    ],
                ),
            ],
        ];
    }

    /**
     * Attributes shown on the view page. Return null to let DetailView display
     * every attribute; override to hide sensitive columns (see UserController).
     *
     * @return array<int, mixed>|null
     */
    public function viewAttributes(): ?array
    {
        return null;
    }

    protected function findModel(int $id): ActiveRecord
    {
        $model = $this->modelClass::findOne($id);

        if ($model === null) {
            throw new NotFoundHttpException('The requested record does not exist.');
        }

        return $model;
    }
}
