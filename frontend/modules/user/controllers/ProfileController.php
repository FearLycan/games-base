<?php

namespace frontend\modules\user\controllers;

use common\models\Game;
use common\models\GameOffer;
use common\models\User;
use common\models\UserAchievement;
use common\models\UserGame;
use common\models\UserWishlist;
use frontend\components\Controller;
use frontend\modules\user\models\AchievementsFilter;
use frontend\modules\user\models\AddEmailForm;
use frontend\modules\user\models\LibraryFilter;
use frontend\modules\user\models\ProfileStats;
use frontend\modules\user\models\WishlistFilter;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Query;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

/**
 * Account area for the signed-in user: shows the linked Steam profile and lets
 * the user add and confirm an email address. (Library / wishlist / achievements
 * sync will hang off this controller in later stages.)
 */
class ProfileController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                // confirm-email is reachable from an email link, possibly while
                // signed out, so it stays open; the rest requires a login.
                'only'  => ['index', 'settings', 'add-email', 'library', 'wishlist', 'achievements', 'sync'],
                'rules' => [
                    [
                        'actions' => ['index', 'settings', 'add-email', 'library', 'wishlist', 'achievements', 'sync'],
                        'allow'   => true,
                        'roles'   => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class'   => VerbFilter::class,
                'actions' => [
                    'add-email' => ['post'],
                    'sync'      => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;

        return $this->render('index', [
            'user'  => $user,
            'stats' => new ProfileStats((int)$user->id),
        ]);
    }

    public function actionSettings()
    {
        return $this->render('settings', [
            'user'         => Yii::$app->user->identity,
            'addEmailForm' => new AddEmailForm(Yii::$app->user->identity),
        ]);
    }

    public function actionAddEmail()
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;
        $model = new AddEmailForm($user);

        if ($model->load(Yii::$app->request->post()) && $model->addEmail()) {
            Yii::$app->session->setFlash('success', 'Check your inbox to confirm your email address.');

            return $this->redirect(['settings']);
        }

        return $this->render('settings', [
            'user'         => $user,
            'addEmailForm' => $model,
        ]);
    }

    public function actionLibrary()
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;

        $filter = new LibraryFilter();
        $filter->load(Yii::$app->request->queryParams);

        // Only games we actually have in the catalogue (synced, with a title) are
        // shown — the rest are queued stubs that surface once synced.
        $query = UserGame::find()
            ->where(['user_game.user_id' => $user->id])
            ->innerJoinWith('game')
            ->andWhere(['game.status' => Game::STATUS_ACTIVE])
            ->andWhere(['not', ['game.title' => null]]);
        $filter->apply($query);

        $dataProvider = new ActiveDataProvider([
            'query'      => $query,
            'pagination' => ['pageSize' => 60],
            'sort'       => false,
        ]);

        $catalogued = $this->countCatalogued(UserGame::find()->where(['user_game.user_id' => $user->id]));
        $owned = (int)UserGame::find()->where(['user_game.user_id' => $user->id])->count();

        return $this->render('library', [
            'user'         => $user,
            'filter'       => $filter,
            'dataProvider' => $dataProvider,
            'libraryCount' => $catalogued,
            'pendingCount' => max(0, $owned - $catalogued),
        ]);
    }

    /**
     * Queues a Steam (re-)sync for the current user. The actual pull runs on the
     * paced cron (SteamUserController), never here — so we just flag it.
     */
    public function actionSync()
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;
        $back = Yii::$app->request->referrer ?: ['library'];

        if (!$user->canRequestSync()) {
            Yii::$app->session->setFlash('warning', 'You can run a full sync again in ' . $user->getSyncCooldownLabel() . '.');

            return $this->redirect($back);
        }

        $user->enqueueFullSync();
        Yii::$app->session->setFlash('success', 'Sync queued — your library, wishlist and achievements will refresh shortly.');

        return $this->redirect($back);
    }

    public function actionWishlist()
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;

        $filter = new WishlistFilter();
        $filter->load(Yii::$app->request->queryParams);

        // Show only wishlisted games we have in the catalogue (the Steam wishlist
        // API gives no name, so off-catalogue rows have nothing to show anyway).
        $query = UserWishlist::find()
            ->where(['user_wishlist.user_id' => $user->id])
            ->innerJoinWith('game', false)
            ->andWhere(['game.status' => Game::STATUS_ACTIVE])
            ->andWhere(['not', ['game.title' => null]])
            // Eager-load each catalogue game + its active offers (store + prices)
            // so the tiles can show a price without a query per card.
            ->with(['game.gameOffers' => function ($q): void {
                $q->andWhere(['game_offer.status' => GameOffer::STATUS_ACTIVE])
                    ->with(['store', 'prices']);
            }]);
        $filter->apply($query);

        $dataProvider = new ActiveDataProvider([
            'query'      => $query,
            'pagination' => ['pageSize' => 60],
            'sort'       => false,
        ]);

        $catalogued = $this->countCatalogued(UserWishlist::find()->where(['user_wishlist.user_id' => $user->id]));
        $total = (int)UserWishlist::find()->where(['user_wishlist.user_id' => $user->id])->count();

        return $this->render('wishlist', [
            'user'          => $user,
            'filter'        => $filter,
            'dataProvider'  => $dataProvider,
            'wishlistCount' => $catalogued,
            'pendingCount'  => max(0, $total - $catalogued),
        ]);
    }

    public function actionAchievements()
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;

        $filter = new AchievementsFilter();
        $filter->load(Yii::$app->request->queryParams);

        $query = UserAchievement::find()
            ->alias('ua')
            ->innerJoinWith('achievement')
            ->innerJoinWith('game')
            ->where(['ua.user_id' => $user->id, 'game.status' => Game::STATUS_ACTIVE]);
        $filter->apply($query);

        $dataProvider = new ActiveDataProvider([
            'query'      => $query,
            'pagination' => ['pageSize' => 60],
            'sort'       => false,
        ]);

        $total = (int)UserAchievement::find()
            ->alias('ua')
            ->innerJoinWith('game')
            ->where(['ua.user_id' => $user->id, 'game.status' => Game::STATUS_ACTIVE])
            ->count();

        return $this->render('achievements', [
            'user'         => $user,
            'filter'       => $filter,
            'dataProvider' => $dataProvider,
            'total'        => $total,
            'games'        => $this->achievementGames((int)$user->id),
        ]);
    }

    /**
     * Map of game id => title for games the user has unlocked achievements in,
     * for the collection's game filter.
     *
     * @return array<int,string>
     */
    private function achievementGames(int $userId): array
    {
        $rows = (new Query())
            ->select(['id' => 'g.id', 'title' => 'g.title'])
            ->distinct()
            ->from(['ua' => UserAchievement::tableName()])
            ->innerJoin(['g' => Game::tableName()], 'g.id = ua.game_id AND g.status = ' . Game::STATUS_ACTIVE)
            ->where(['ua.user_id' => $userId])
            ->andWhere(['not', ['g.title' => null]])
            ->orderBy(['g.title' => SORT_ASC])
            ->all();

        return array_column($rows, 'title', 'id');
    }

    /**
     * Counts only rows linked to a catalogued (active, titled) game — the same
     * "in the catalogue" rule the library/wishlist lists filter by.
     */
    private function countCatalogued(ActiveQuery $query): int
    {
        return (int)$query
            ->innerJoinWith('game', false)
            ->andWhere(['game.status' => Game::STATUS_ACTIVE])
            ->andWhere(['not', ['game.title' => null]])
            ->count();
    }

    public function actionConfirmEmail(string $token)
    {
        $user = User::findByEmailVerificationToken($token);

        if ($user === null || $user->isEmailVerified()) {
            Yii::$app->session->setFlash('error', 'This confirmation link is invalid or has already been used.');

            return $this->goHome();
        }

        $user->email_verified = true;
        $user->verification_token = null;
        $user->save(false);

        Yii::$app->session->setFlash('success', 'Your email address has been confirmed.');

        return Yii::$app->user->isGuest
            ? $this->redirect(['/user/auth/login'])
            : $this->redirect(['index']);
    }
}
