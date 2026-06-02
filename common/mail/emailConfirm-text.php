<?php

/* @var $this yii\web\View */
/* @var $user common\models\User */

$confirmLink = Yii::$app->urlManager->createAbsoluteUrl(['user/profile/confirm-email', 'token' => $user->verification_token]);
?>
Hello <?= $user->username ?>,

Follow the link below to confirm your email address:

<?= $confirmLink ?>
