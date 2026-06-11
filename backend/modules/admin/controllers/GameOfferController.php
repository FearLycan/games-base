<?php

namespace backend\modules\admin\controllers;

use backend\models\GameOffer;
use backend\models\Store;
use backend\modules\admin\components\AdminHtml;
use backend\modules\admin\models\search\GameOfferSearch;
use Yii;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\Response;

class GameOfferController extends CrudController
{
    public string $modelClass = GameOffer::class;
    public string $searchModelClass = GameOfferSearch::class;
    public string $modelLabel = 'Offer';
    public string $modelLabelPlural = 'Offers';

    // Offers have three states; the grid uses a pill + dropdown filter and
    // dedicated accept/reject actions rather than the on/off switch.
    protected ?string $toggleAttribute = 'status';
    protected int $toggleOn = GameOffer::STATUS_ACTIVE;
    protected int $toggleOff = GameOffer::STATUS_INACTIVE;

    /**
     * Purpose-built review screen: the offer link front and centre, the game it
     * was matched to alongside it, and accept/reject in one click.
     */
    public function actionView(int $id): string
    {
        return $this->render('view', [
            'model'      => $this->findModel($id),
            'controller' => $this,
        ]);
    }

    /**
     * Confirms a pending offer (REVIEW -> ACTIVE).
     */
    public function actionAccept(int $id): Response
    {
        return $this->transition($id, GameOffer::STATUS_ACTIVE, 'accepted', GameOffer::STATUS_REVIEW);
    }

    /**
     * Dismisses an offer (-> INACTIVE), from review or active.
     */
    public function actionReject(int $id): Response
    {
        return $this->transition($id, GameOffer::STATUS_INACTIVE, 'rejected');
    }

    /**
     * Applies a status change. Answers JSON for AJAX (the list rows), otherwise
     * flashes and jumps to the next offer still awaiting review so the queue can
     * be cleared without returning to the list each time.
     */
    private function transition(int $id, int $newStatus, string $verb, ?int $fromStatus = null): Response
    {
        $isAjax = Yii::$app->request->isAjax;
        $model = $this->findModel($id);

        if ($fromStatus !== null && (int)$model->status !== $fromStatus) {
            $message = 'This offer is not pending review.';
            return $this->respond($isAjax, false, $message, $id);
        }

        $model->status = $newStatus;
        $ok = $model->save(false, ['status', 'updated_at']);
        $message = $ok ? ('Offer ' . $verb . '.') : 'Could not update the offer.';

        return $this->respond($isAjax, $ok, $message, $id);
    }

    private function respond(bool $isAjax, bool $ok, string $message, int $id): Response
    {
        if ($isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return $this->asJson(['success' => $ok, 'message' => $message]);
        }

        Yii::$app->session->setFlash($ok ? 'success' : 'error', $message);

        if (!$ok) {
            return $this->redirect(['view', 'id' => $id]);
        }

        $next = GameOffer::find()
            ->where(['status' => GameOffer::STATUS_REVIEW])
            ->andWhere(['<>', 'id', $id])
            ->orderBy(['id' => SORT_ASC])
            ->one();

        return $next ? $this->redirect(['view', 'id' => $next->id]) : $this->redirect(['index']);
    }

    protected function gridColumns(): array
    {
        return [
            'id',
            [
                'attribute'         => 'gameTitle',
                'label'             => 'Game',
                'format'            => 'raw',
                'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Name or App ID'],
                'value'             => static fn(GameOffer $m): string => AdminHtml::gameLink($m->game, $m->game_id),
            ],
            [
                'attribute' => 'store_id',
                'label'     => 'Store',
                'filter'    => Store::find()->orderBy(['name' => SORT_ASC])->select('name')->indexBy('id')->column(),
                'value'     => static fn(GameOffer $m): string => $m->store->name ?? (string)$m->store_id,
            ],
            'region',
            'edition',
            [
                'label'  => 'Prices',
                'format' => 'raw',
                'value'  => static function (GameOffer $m): string {
                    $parts = [];
                    foreach ($m->prices as $price) {
                        $parts[] = strtoupper((string)$price->currency) . ' '
                            . number_format(((int)$price->price_final) / 100, 2);
                    }
                    return $parts ? Html::encode(implode(' · ', $parts)) : '—';
                },
            ],
            [
                'attribute' => 'status',
                'format'    => 'raw',
                'filter'    => [
                    GameOffer::STATUS_REVIEW   => 'Review',
                    GameOffer::STATUS_ACTIVE   => 'Active',
                    GameOffer::STATUS_INACTIVE => 'Inactive',
                ],
                'value'     => static fn(GameOffer $m): string => AdminHtml::offerStatusPill((int)$m->status),
            ],
            $this->actionColumn(),
        ];
    }

    /**
     * Review queue rows get one-click accept/reject (AJAX) plus an "open offer"
     * shortcut, ahead of the standard view/edit/delete actions.
     */
    protected function actionColumn(): array
    {
        $column = parent::actionColumn();
        $column['template'] = '{visit} {accept} {reject} ' . $column['template'];

        $column['buttons']['visit'] = static fn(string $url, GameOffer $model): string => Html::a(
            '<i class="bi bi-box-arrow-up-right"></i>',
            $model->url,
            ['class' => 'text-secondary me-2', 'title' => 'Open offer page', 'target' => '_blank', 'rel' => 'noopener'],
        );

        $column['buttons']['accept'] = static function (string $url, GameOffer $model): string {
            if ((int)$model->status !== GameOffer::STATUS_REVIEW) {
                return '';
            }
            return Html::a('<i class="bi bi-check2-circle"></i>', Url::to(['accept', 'id' => $model->id]), [
                'class' => 'btn-accept js-ajax-accept me-2',
                'title' => 'Accept offer',
            ]);
        };

        $column['buttons']['reject'] = static function (string $url, GameOffer $model): string {
            if ((int)$model->status !== GameOffer::STATUS_REVIEW) {
                return '';
            }
            return Html::a('<i class="bi bi-x-circle"></i>', Url::to(['reject', 'id' => $model->id]), [
                'class' => 'text-danger js-ajax-reject me-2',
                'title' => 'Reject offer',
            ]);
        };

        return $column;
    }
}
