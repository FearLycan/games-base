<?php

namespace backend\modules\admin;

/**
 * Admin module: GridView-based management back office for the catalogue models.
 *
 * Access is restricted to ROLE_ADMIN users; that check lives on
 * {@see controllers\BaseAdminController} so every controller in the module
 * inherits it. The module reuses the backend application layout.
 */
class Module extends \yii\base\Module
{
    public $controllerNamespace = 'backend\modules\admin\controllers';

    /** Use the module's own sidebar layout (views/layouts/main.php). */
    public $layout = 'main';

    public function init(): void
    {
        parent::init();
    }
}
