<?php

namespace backend\modules\admin\controllers;

use backend\models\IpAddress;
use backend\modules\admin\models\search\IpAddressSearch;
use yii\db\ActiveRecord;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\Response;
use Yii;

/**
 * Admin screen for the IP registry: list every source that has generated an
 * error, see who it is / how many and which errors, and block or unblock it.
 *
 * The inline status switch ({@see CrudController::statusColumn()}) is wired to
 * `is_blocked`; toggling it also stamps `blocked_at` (see
 * {@see actionToggleStatus()}). The block list cache is kept fresh by the model
 * itself ({@see \common\models\IpAddress::afterSave()}).
 */
class IpAddressController extends CrudController
{
    public string $modelClass = IpAddress::class;
    public string $searchModelClass = IpAddressSearch::class;
    public string $modelLabel = 'IP Address';
    public string $modelLabelPlural = 'IP Addresses';

    protected ?string $toggleAttribute = 'is_blocked';
    protected int $toggleOn = IpAddress::BLOCKED;
    protected int $toggleOff = IpAddress::NOT_BLOCKED;

    /** Purpose-built detail screen: IP info + status breakdown + recent errors. */
    public function actionView(int $id): string
    {
        return $this->render('view', [
            'model'      => $this->findModel($id),
            'controller' => $this,
        ]);
    }

    /**
     * Flips the block flag and stamps/clears `blocked_at` to match. The grid
     * switch hits this over AJAX (JSON reply); the view-page button posts plain
     * and gets a redirect. Cache invalidation happens in the model's afterSave.
     */
    public function actionToggleStatus(int $id): Response
    {
        $isAjax = Yii::$app->request->isAjax;

        /** @var IpAddress $model */
        $model = $this->findModel($id);

        $block = !$model->isBlocked();
        $model->is_blocked = $block ? IpAddress::BLOCKED : IpAddress::NOT_BLOCKED;
        $model->blocked_at = $block ? date('Y-m-d H:i:s') : null;

        $saved = $model->save(false, ['is_blocked', 'blocked_at', 'updated_at']);
        $message = $saved
            ? ($block ? 'IP blocked.' : 'IP unblocked.')
            : 'Could not save the new status.';

        if ($isAjax) {
            return $this->asJson(['success' => $saved, 'active' => $block, 'message' => $message]);
        }

        Yii::$app->session->setFlash($saved ? 'success' : 'error', $message);
        return $this->redirect(['view', 'id' => $model->id]);
    }

    /** Filter dropdown for the block column reads as Blocked/Allowed, not Active/Inactive. */
    protected function statusFilterOptions(): array
    {
        return [
            IpAddress::BLOCKED     => 'Blocked',
            IpAddress::NOT_BLOCKED => 'Allowed',
        ];
    }

    protected function gridColumns(): array
    {
        return [
            'id',
            [
                'attribute' => 'ip',
                'format'    => 'raw',
                'value'     => static fn(ActiveRecord $model): string => Html::a(
                    Html::encode($model->ip),
                    Url::to(['view', 'id' => $model->id]),
                    ['class' => 'grid-name-link'],
                ),
            ],
            [
                'attribute'      => 'country',
                'headerOptions'  => ['style' => 'width:80px'],
                'value'          => static fn(ActiveRecord $model): string => $model->country ?: '—',
            ],
            'note',
            [
                'attribute'      => 'error_count',
                'headerOptions'  => ['style' => 'width:90px'],
                'contentOptions' => ['class' => 'text-end'],
            ],
            [
                'attribute'      => 'last_status',
                'headerOptions'  => ['style' => 'width:100px'],
                'value'          => static fn(ActiveRecord $model): string => $model->last_status ? (string)$model->last_status : '—',
            ],
            [
                'attribute'      => 'last_seen_at',
                'format'         => 'datetime',
                'headerOptions'  => ['style' => 'width:160px'],
            ],
            $this->statusColumn(),
            $this->actionColumn(),
        ];
    }
}
