<?php

namespace frontend\modules\api\controllers;

use common\components\GameQuery;
use common\models\Game;
use common\models\GameOffer;
use frontend\modules\api\components\GameSerializer;
use Yii;
use yii\db\Query;
use yii\web\NotFoundHttpException;

/**
 * Catalogue feed: `/api/games` (list) and `/api/games/<steam_appid>` (one game).
 */
class GameController extends BaseController
{
    /**
     * Paginated catalogue feed.
     *
     * Query parameters:
     *   page, per_page      pagination (per_page capped at {@see MAX_PER_PAGE})
     *   include             sections to embed, see {@see GameSerializer::ALL_INCLUDES};
     *                       `all` for everything, omitted for the defaults
     *   currency            comma-separated filter for offer prices (EUR,USD,PLN)
     *   type                game | dlc | music | demo | all   (default: game)
     *   updated_since       only rows changed at/after this timestamp — drives
     *                       incremental imports; see the `changed_at` field
     *   released_from/_to   release-date window (YYYY-MM-DD), for calendar-style
     *                       consumers
     *   has_release_date    1 = skip rows whose Steam date was too vague to parse
     *   with_offers         1 = only games that have an active store offer
     *   include_adult       1 = include 18+ titles (excluded by default)
     *   include_inactive    1 = include rows not yet/no longer synced
     *
     * Ordering is by `id ASC` so offset pagination stays stable while a sync is
     * running (ids never change; `updated_at` does).
     */
    public function actionIndex(): array
    {
        $request = Yii::$app->request;
        $serializer = new GameSerializer(
            GameSerializer::parseIncludes($request->get('include')),
            $this->currencies(),
        );

        $query = $this->buildQuery();
        $total = (int)$query->count();

        $games = $query
            ->with($serializer->eagerLoad())
            ->orderBy(['game.id' => SORT_ASC])
            ->offset(($this->page() - 1) * $this->perPage())
            ->limit($this->perPage())
            ->all();

        $items = array_map(
            static fn(Game $game): array => $serializer->serialize($game),
            $games,
        );

        return $this->paginated($items, $total, [
            'price_units'           => 'minor', // cents / grosze
            'steam_price_currency'  => 'USD',
        ]);
    }

    /**
     * A single game by Steam appid — the key both sides share, unlike our
     * internal `id`.
     *
     * @throws NotFoundHttpException
     */
    public function actionView(int $appid): array
    {
        $request = Yii::$app->request;
        // A single game is cheap, so default to the full payload here.
        $serializer = new GameSerializer(
            GameSerializer::parseIncludes($request->get('include', 'all')),
            $this->currencies(),
        );

        $game = Game::find()
            ->alias('game')
            ->where(['game.steam_appid' => $appid])
            ->with($serializer->eagerLoad())
            ->one();

        if ($game === null) {
            throw new NotFoundHttpException('Game not found.');
        }

        return [
            'item' => $serializer->serialize($game),
            'meta' => [
                'price_units'          => 'minor',
                'steam_price_currency' => 'USD',
                'generated_at'         => date('c'),
            ],
        ];
    }

    /**
     * Shared filtering for the list endpoint.
     *
     * @throws \yii\web\BadRequestHttpException
     */
    private function buildQuery(): GameQuery
    {
        $request = Yii::$app->request;

        $query = Game::find()->alias('game');

        if (!$request->get('include_inactive')) {
            $query->andWhere(['game.status' => Game::STATUS_ACTIVE]);
        }

        // Only base games by default: the table also holds DLC, soundtracks and
        // demos, which would otherwise flood an importer.
        $type = strtolower(trim((string)$request->get('type', Game::TYPE_GAME)));
        if ($type !== 'all' && $type !== '') {
            $query->andWhere(['game.type' => $type]);
        }

        if (!$request->get('include_adult')) {
            $query->andWhere(['game.is_adult' => 0]);
        }

        $updatedSince = $this->dateParam('updated_since');
        if ($updatedSince !== null) {
            // Matches the serialised `changed_at`: any of the three timestamps
            // moving means the row is worth re-reading. All three are indexed.
            $query->andWhere(['or',
                ['>=', 'game.created_at', $updatedSince],
                ['>=', 'game.updated_at', $updatedSince],
                ['>=', 'game.synchronized_at', $updatedSince],
            ]);
        }

        $releasedFrom = $this->dateParam('released_from');
        if ($releasedFrom !== null) {
            $query->andWhere(['>=', 'game.release_date', $releasedFrom]);
        }

        $releasedTo = $this->dateParam('released_to');
        if ($releasedTo !== null) {
            $query->andWhere(['<=', 'game.release_date', $releasedTo]);
        }

        if ($request->get('has_release_date')) {
            $query->andWhere(['not', ['game.release_date' => null]]);
        }

        if ($request->get('with_offers')) {
            $query->andWhere(['exists', (new Query())
                ->from('{{%game_offer}} o')
                ->where('o.game_id = game.id')
                ->andWhere(['o.status' => GameOffer::STATUS_ACTIVE])]);
        }

        return $query;
    }
}
