<?php

use backend\modules\admin\components\AdminHtml;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Platform */
/* @var $controller backend\modules\admin\controllers\PlatformController */

$this->title = (string)$model->name ?: ('Platform #' . $model->id);
$this->params['breadcrumbs'][] = ['label' => 'Platforms', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$game = $model->game;
?>
<div class="admin-page-header">
    <h1><?= Html::encode($this->title) ?> <?= AdminHtml::activePill((int)$model->available, 'Available', 'Unavailable') ?></h1>
    <div class="d-flex gap-2">
        <?= Html::a('<i class="bi bi-pencil-square"></i> Edit', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('<i class="bi bi-trash"></i> Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-outline-danger',
            'data'  => ['confirm' => 'Delete this platform permanently?', 'method' => 'post'],
        ]) ?>
    </div>
</div>

<section class="offer-panel mb-3">
    <h2 class="offer-panel__title">Details</h2>
    <dl class="offer-dl offer-dl--wide">
        <dt>Name</dt><dd><?= Html::encode((string)$model->name) ?></dd>
        <dt>Available</dt><dd><?= AdminHtml::activePill((int)$model->available, 'Available', 'Unavailable') ?></dd>
        <dt>Game</dt><dd><?= AdminHtml::gameLink($game, $model->game_id) ?></dd>
    </dl>
</section>

<?php if ($model->requirements_minimum || $model->requirements_recommended): ?>
    <div class="offer-grid">
        <?php if ($model->requirements_minimum): ?>
            <section class="offer-panel">
                <h2 class="offer-panel__title">Minimum requirements</h2>
                <div class="game-richtext"><?= $model->requirements_minimum ?></div>
            </section>
        <?php endif; ?>
        <?php if ($model->requirements_recommended): ?>
            <section class="offer-panel">
                <h2 class="offer-panel__title">Recommended requirements</h2>
                <div class="game-richtext"><?= $model->requirements_recommended ?></div>
            </section>
        <?php endif; ?>
    </div>
<?php endif; ?>
