<?php

use backend\modules\admin\components\AdminHtml;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\IpAddress */
/* @var $controller backend\modules\admin\controllers\IpAddressController */

$this->title = $model->ip;
$this->params['breadcrumbs'][] = ['label' => 'IP Addresses', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$blocked = $model->isBlocked();
$breakdown = $model->statusBreakdown();
$recent = $model->getErrorLog()->limit(50)->all();

$blockedPill = Html::tag(
    'span',
    $blocked ? 'Blocked' : 'Allowed',
    ['class' => 'admin-pill admin-pill--' . ($blocked ? 'inactive' : 'active')],
);
?>
<div class="admin-page-header">
    <h1><?= Html::encode($this->title) ?> <?= $blockedPill ?><?php if ($model->isBot()): ?> <span class="admin-pill admin-pill--review">Bot</span><?php endif; ?></h1>
    <div class="d-flex gap-2">
        <?= Html::beginForm(['toggle-status', 'id' => $model->id]) ?>
        <?= Html::submitButton(
            $blocked ? '<i class="bi bi-unlock"></i> Unblock' : '<i class="bi bi-lock"></i> Block',
            [
                'class' => 'btn ' . ($blocked ? 'btn-outline-success' : 'btn-danger'),
                'data'  => ['confirm' => $blocked
                    ? 'Allow this IP again?'
                    : 'Block this IP from the public site?'],
            ],
        ) ?>
        <?= Html::endForm() ?>
        <?= Html::a('<i class="bi bi-pencil-square"></i> Edit', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('<i class="bi bi-trash"></i> Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-outline-danger',
            'data'  => ['confirm' => 'Delete this IP and its error history permanently?', 'method' => 'post'],
        ]) ?>
    </div>
</div>

<div class="offer-grid">
    <section class="offer-panel">
        <h2 class="offer-panel__title">Details</h2>
        <dl class="offer-dl">
            <dt>IP address</dt><dd><code><?= Html::encode($model->ip) ?></code></dd>
            <dt>Who is this</dt><dd><?= $model->note ? Html::encode($model->note) : '<span class="text-muted">—</span>' ?></dd>
            <dt>Country</dt><dd><?= $model->country ? Html::encode($model->country) : '<span class="text-muted">—</span>' ?></dd>
            <dt>Total errors</dt><dd><strong><?= number_format((int)$model->error_count) ?></strong></dd>
            <dt>Last status</dt><dd><?= $model->last_status ? (int)$model->last_status : '<span class="text-muted">—</span>' ?></dd>
            <dt>Last path</dt><dd><?= $model->last_path ? Html::tag('code', Html::encode($model->last_path)) : '<span class="text-muted">—</span>' ?></dd>
            <dt>First seen</dt><dd class="text-muted"><?= Html::encode((string)$model->first_seen_at) ?></dd>
            <dt>Last seen</dt><dd class="text-muted"><?= Html::encode((string)($model->last_seen_at ?? '—')) ?></dd>
            <?php if ($blocked): ?>
                <dt>Blocked at</dt><dd class="text-muted"><?= Html::encode((string)($model->blocked_at ?? '—')) ?></dd>
                <dt>Block reason</dt><dd><?= $model->block_reason ? Html::encode($model->block_reason) : '<span class="text-muted">—</span>' ?></dd>
            <?php endif; ?>
            <dt>User agent</dt><dd class="text-muted"><?= $model->last_user_agent ? Html::encode($model->last_user_agent) : '—' ?></dd>
        </dl>
    </section>

    <section class="offer-panel">
        <h2 class="offer-panel__title">Errors by status</h2>
        <?php if ($breakdown === []): ?>
            <p class="text-muted mb-0">No errors recorded for this IP yet.</p>
        <?php else: ?>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($breakdown as $status => $count): ?>
                    <span class="admin-pill admin-pill--review">
                        <?= (int)$status ?> · <?= number_format((int)$count) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<section class="offer-panel mt-3">
    <h2 class="offer-panel__title">Recent errors</h2>
    <?php if ($recent === []): ?>
        <p class="text-muted mb-0">Nothing logged yet.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:160px">When</th>
                        <th style="width:80px">Status</th>
                        <th style="width:70px">Method</th>
                        <th>Path</th>
                        <th>Referrer</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $error): ?>
                        <tr>
                            <td class="text-muted"><?= Html::encode((string)$error->created_at) ?></td>
                            <td><?= (int)$error->status ?></td>
                            <td><?= Html::encode((string)($error->method ?? '—')) ?></td>
                            <td><code><?= Html::encode((string)$error->path) ?></code></td>
                            <td class="text-muted"><?= $error->referrer ? Html::encode($error->referrer) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
