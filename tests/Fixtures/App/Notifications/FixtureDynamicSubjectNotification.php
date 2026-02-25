<?php

namespace Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FixtureDynamicSubjectNotification extends Notification
{
    public function __construct(
        public object $user
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $subject = 'Hello '.$this->user->name;

        return (new MailMessage)
            ->subject($subject);
    }
}
