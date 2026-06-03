<?php

namespace frontend\controllers;

use common\components\NotFoundLogger;
use common\models\Genre;
use common\models\Tag;
use frontend\models\ContactForm;
use Throwable;
use Yii;
use yii\web\Controller;

/**
 * Site controller — public, non-account pages. Authentication and account
 * management live in the `user` module (AuthController / ProfileController).
 */
class SiteController extends Controller
{
    /**
     * Replaces yii\web\ErrorAction so the error page can surface discovery
     * widgets (top genres + tags) on a 404 — turns a dead-end into a way out.
     * Mirrors ErrorAction's status-code handling.
     */
    public function actionError()
    {
        $exception = Yii::$app->errorHandler->exception;
        if ($exception === null) {
            $exception = new \yii\web\HttpException(404, 'Page not found.');
        }

        Yii::$app->response->setStatusCodeByException($exception);

        // Capture referrer + request context so broken inbound links can be traced.
        NotFoundLogger::log($exception);

        $name = method_exists($exception, 'getName') ? $exception->getName() : 'Error';
        $message = $exception->getMessage();

        $genres = [];
        $tags = [];

        if ($exception instanceof \yii\web\HttpException && $exception->statusCode === 404) {
            try {
                $genres = Yii::$app->cache->getOrSet('error.popular_genres', static fn(): array => Genre::find()
                    ->where(['>', 'games_count', 0])
                    ->orderBy(['games_count' => SORT_DESC])
                    ->limit(6)
                    ->all(), 3600);

                $tags = Yii::$app->cache->getOrSet('error.popular_tags', static fn(): array => Tag::find()
                    ->where(['>', 'games_count', 0])
                    ->orderBy(['games_count' => SORT_DESC])
                    ->limit(12)
                    ->all(), 3600);
            } catch (Throwable $e) {
                // DB might be the failure cause — degrade gracefully, render error page anyway
            }
        }

        return $this->render('error', [
            'name'      => $name,
            'message'   => $message,
            'exception' => $exception,
            'genres'    => $genres,
            'tags'      => $tags,
        ]);
    }

    public function actionHowItWorks()
    {
        return $this->render('how-it-works');
    }

    /**
     * Displays contact page.
     *
     * @return mixed
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail(Yii::$app->params['adminEmail'])) {
                Yii::$app->session->setFlash('success', 'Thank you for contacting us. We will respond to you as soon as possible.');
            } else {
                Yii::$app->session->setFlash('error', 'There was an error sending your message.');
            }

            return $this->refresh();
        }

        return $this->render('contact', [
            'model' => $model,
        ]);
    }
}
