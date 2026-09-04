<?php

namespace frontend\modules\api\controllers;

use Yii;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\ServiceUnavailableHttpException;

/**
 * Shared plumbing for the internal API: JSON responses, static-key auth and
 * no-index headers.
 *
 * Extends yii\web\Controller directly rather than the site's base controller:
 * that one denies guests by default (session login), which is the wrong model
 * here — API clients are servers carrying a key, not logged-in users.
 */
abstract class BaseController extends Controller
{
    /** GET-only, key-authenticated: no browser session, nothing to forge. */
    public $enableCsrfValidation = false;

    protected const int DEFAULT_PER_PAGE = 100;
    protected const int MAX_PER_PAGE     = 500;

    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class'   => \yii\filters\VerbFilter::class,
                'actions' => ['*' => ['GET', 'HEAD']],
            ],
        ];
    }

    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        // Belt and braces: the routes are unlinked, but a leaked URL must not
        // end up in an index.
        Yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        $this->checkApiKey();

        return true;
    }

    /**
     * @throws ForbiddenHttpException      when the caller's key is missing/wrong
     * @throws ServiceUnavailableHttpException when no key is configured at all —
     *         failing closed, so a forgotten `internalApiKey` can never expose
     *         the catalogue to everyone.
     */
    protected function checkApiKey(): void
    {
        $expected = (string)(Yii::$app->params['internalApiKey'] ?? '');

        if ($expected === '') {
            throw new ServiceUnavailableHttpException('Internal API is not configured.');
        }

        $provided = (string)Yii::$app->request->headers->get('X-Api-Key', '');

        if (!hash_equals($expected, $provided)) {
            throw new ForbiddenHttpException('Invalid API key.');
        }
    }

    /** Page number from the query string, 1-based. */
    protected function page(): int
    {
        return max(1, (int)Yii::$app->request->get('page', 1));
    }

    /** Page size from the query string, clamped to {@see MAX_PER_PAGE}. */
    protected function perPage(): int
    {
        $perPage = (int)Yii::$app->request->get('per_page', self::DEFAULT_PER_PAGE);

        return max(1, min(self::MAX_PER_PAGE, $perPage ?: self::DEFAULT_PER_PAGE));
    }

    /**
     * Uppercased currency codes from `?currency=EUR,PLN`, or null for "all".
     *
     * @return string[]|null
     */
    protected function currencies(): ?array
    {
        $raw = trim((string)Yii::$app->request->get('currency', ''));
        if ($raw === '') {
            return null;
        }

        $codes = array_values(array_filter(array_map(
            static fn(string $code): string => strtoupper(trim($code)),
            explode(',', $raw),
        )));

        return $codes ?: null;
    }

    /**
     * A `Y-m-d[ H:i:s]` / ISO-8601 timestamp from the query string, normalised to
     * the DB's datetime format, or null when absent. Unparseable input is
     * rejected loudly rather than silently returning the whole table.
     *
     * @throws \yii\web\BadRequestHttpException
     */
    protected function dateParam(string $name): ?string
    {
        $raw = trim((string)Yii::$app->request->get($name, ''));
        if ($raw === '') {
            return null;
        }

        $timestamp = strtotime($raw);
        if ($timestamp === false) {
            throw new \yii\web\BadRequestHttpException("Invalid `$name` — expected a date or datetime.");
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    /**
     * Envelope shared by every list endpoint. `total_pages`/`total` are computed
     * from a COUNT, so clients can either follow `has_more` or drive a bounded
     * loop.
     *
     * @param array<int, mixed> $items
     */
    protected function paginated(array $items, int $total, array $extra = []): array
    {
        $perPage = $this->perPage();
        $page = $this->page();

        return array_merge([
            'items' => $items,
            'meta'  => array_merge([
                'page'         => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'total_pages'  => (int)ceil($total / $perPage),
                'has_more'     => $page * $perPage < $total,
                'generated_at' => date('c'),
            ], $extra),
        ]);
    }
}
