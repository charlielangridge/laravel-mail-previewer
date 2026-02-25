<?php

namespace Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Notifications;

use Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Models\FixtureRecipient;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FixtureRenderableNotification extends Notification
{
    public function __construct(
        public FixtureRecipient $recipient
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Renderable Notification')
            ->line('Hello '.$this->recipient->name);
    }
}
