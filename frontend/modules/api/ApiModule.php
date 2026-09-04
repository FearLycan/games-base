<?php

namespace frontend\modules\api;

/**
 * Internal read-only JSON API.
 *
 * Exists so sister projects (currently the release-calendar app) can pull this
 * catalogue instead of re-crawling Steam and the keyshops themselves. Every
 * endpoint is protected by a static key (`params['internalApiKey']`) — it is
 * not a public API and is never linked or indexed.
 */
class ApiModule extends \yii\base\Module
{
    public $controllerNamespace = 'frontend\modules\api\controllers';
}
