<?php

namespace Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FixtureNotification extends Notification
{
    public function __construct(
        public int $accountId,
        public string $email
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Fixture Notification Subject');
    }
}
