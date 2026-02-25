<?php

namespace Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FixtureUntypedNotification extends Notification
{
    public function __construct(
        public $recipientId,
        public mixed $payload
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Untyped Notification');
    }
}
