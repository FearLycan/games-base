<?php

namespace frontend\models;

use Yii;
use yii\base\Model;

class ContactForm extends Model
{
    public string $name = '';
    public string $email = '';
    public string $subject = '';
    public string $body = '';

    public function rules(): array
    {
        return [
            [['name', 'email', 'subject', 'body'], 'required'],
            ['name', 'string', 'max' => 50],
            ['email', 'email'],
            ['email', 'string', 'max' => 60],
            ['subject', 'string', 'max' => 200],
            ['body', 'string', 'max' => 3000],
        ];
    }

    public function sendEmail(string $email): bool
    {
        return Yii::$app->mailer->compose()
            ->setTo($email)
            ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
            ->setReplyTo([$this->email => $this->name])
            ->setSubject($this->subject)
            ->setTextBody($this->body)
            ->send();
    }
}
