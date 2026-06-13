<?php

namespace common\components;

use Yii;
use yii\base\ActionFilter;
use yii\web\Response;

/**
 * Makes anonymous GET pages cacheable.
 *
 * Out of the box every request starts a PHP session, which makes PHP emit
 * `Set-Cookie: session=…` plus `Cache-Control: no-store, no-cache,
 * must-revalidate`. That forces the browser AND any CDN in front (Cloudflare) to
 * treat every page as dynamic, so each hit — bot or human — costs a full PHP
 * render on the origin. On shared hosting a handful of crawlers is enough to
 * saturate the PHP workers.
 *
 * For a request that carries no auth/session cookie (a fresh visitor or, in
 * practice, a cookie-less bot) issuing a safe GET, this filter:
 *   - stops PHP from writing the `session` cookie and the automatic `no-store`
 *     headers (an anonymous page view needs no session), and
 *   - on a 200 response, replaces them with `Cache-Control: public, max-age=N`
 *     so the response can be reused.
 *
 * Anyone with a session (`_identity` remember-me cookie OR the PHP `session`
 * cookie) is left completely untouched — they keep their dynamic, no-store
 * behaviour and never get a cached page. Attach this ONLY to read-only
 * controllers; never to auth/form flows (the Steam OpenID callback keeps its
 * handshake in the session, and form pages must not have a stale CSRF token).
 *
 * NOTE on SHARED caches: store-offer prices vary by the visitor's currency
 * (geo-IP / cookie, see {@see CurrencyResolver}). A per-user browser cache is
 * always safe, but a shared CDN cache must vary its cache key by currency or it
 * will serve one visitor's prices to another. The origin-side {@see \yii\filters\PageCache}
 * (varied by currency) is what actually offloads the workers here.
 */
class GuestCacheControl extends ActionFilter
{
    /** Max-age advertised for cacheable anonymous pages, in seconds. */
    public int $duration = 300;

    public function beforeAction($action): bool
    {
        if ($this->isAnonymousGet()) {
            // No session for anonymous page views: don't write the session cookie
            // and don't let PHP auto-send `no-store`/`Expires`/`Pragma`.
            Yii::$app->session->setUseCookies(false);
            @ini_set('session.cache_limiter', '');

            // Set the caching headers at send time so we win even when PageCache
            // serves a hit and restores its own (no-store) headers.
            $duration = $this->duration;
            Yii::$app->response->on(Response::EVENT_BEFORE_SEND, static function ($event) use ($duration): void {
                /** @var Response $response */
                $response = $event->sender;
                if ($response->statusCode !== 200) {
                    return;
                }
                $response->headers->set('Cache-Control', 'public, max-age=' . $duration);
                $response->headers->remove('Pragma');
                $response->headers->remove('Expires');
            });
        }

        return parent::beforeAction($action);
    }

    /**
     * True for a GET request that carries neither the remember-me identity cookie
     * nor a PHP session cookie — i.e. nobody we must keep a session for. Read from
     * $_COOKIE directly: the PHP `session` cookie is not a signed Yii cookie, so
     * it never appears in the validated request cookie collection.
     */
    private function isAnonymousGet(): bool
    {
        if (!Yii::$app->request->isGet) {
            return false;
        }

        $identityCookie = Yii::$app->user->identityCookie['name'] ?? '_identity';
        $sessionCookie = Yii::$app->session->getName();

        return !isset($_COOKIE[$identityCookie]) && !isset($_COOKIE[$sessionCookie]);
    }
}
