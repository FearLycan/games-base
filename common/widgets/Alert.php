<?php

namespace common\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\Html;

/**
 * Renders flash messages from the session. Each app (frontend / backend) can
 * pass its own class map via $alertTypes to match its CSS framework.
 *
 * ```php
 * Yii::$app->session->setFlash('error', 'This is the message');
 * ```
 */
class Alert extends Widget
{
    /**
     * @var array<string, string> Map of flash key → CSS class.
     * Defaults to Bootstrap markup so backend keeps working unchanged.
     */
    public $alertTypes = [
        'error'   => 'alert alert-danger',
        'danger'  => 'alert alert-danger',
        'success' => 'alert alert-success',
        'info'    => 'alert alert-info',
        'warning' => 'alert alert-warning',
    ];

    /** @var array Extra HTML options merged into every rendered alert. */
    public $options = [];

    public function run()
    {
        $session = Yii::$app->session;
        $flashes = $session->getAllFlashes();

        foreach ($flashes as $type => $flash) {
            if (!isset($this->alertTypes[$type])) {
                continue;
            }

            foreach ((array) $flash as $i => $message) {
                $options = array_merge($this->options, [
                    'id' => $this->getId() . '-' . $type . '-' . $i,
                ]);
                $options['class'] = trim(($options['class'] ?? '') . ' ' . $this->alertTypes[$type]);

                echo Html::tag('div', Html::encode($message), $options);
            }

            $session->removeFlash($type);
        }
    }
}
