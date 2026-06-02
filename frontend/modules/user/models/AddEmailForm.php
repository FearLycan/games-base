<?php

namespace frontend\modules\user\models;

use common\models\User;
use Yii;
use yii\base\Model;

/**
 * Lets a signed-in (Steam) account add an email address and trigger a
 * confirmation mail. The email is stored straight away but marked unverified
 * until the user clicks the link (profile/confirm-email), so an unconfirmed
 * address never counts as verified.
 */
class AddEmailForm extends Model
{
    public ?string $email = null;

    private User $user;

    public function __construct(User $user, array $config = [])
    {
        $this->user = $user;
        parent::__construct($config);
    }

    public function rules(): array
    {
        return [
            ['email', 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            [
                'email',
                'unique',
                'targetClass' => User::class,
                'filter'      => fn($query) => $query->andWhere(['<>', 'id', $this->user->id]),
                'message'     => 'This email address has already been taken.',
            ],
        ];
    }

    /**
     * Stores the (unverified) email and sends the confirmation link.
     */
    public function addEmail(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $this->user->email = $this->email;
        $this->user->email_verified = false;
        $this->user->generateEmailVerificationToken();

        return $this->user->save(false) && $this->sendConfirmationEmail();
    }

    private function sendConfirmationEmail(): bool
    {
        return Yii::$app->mailer
            ->compose(
                ['html' => 'emailConfirm-html', 'text' => 'emailConfirm-text'],
                ['user' => $this->user]
            )
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name . ' robot'])
            ->setTo($this->email)
            ->setSubject('Confirm your email at ' . Yii::$app->name)
            ->send();
    }
}
