<?php

namespace Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Notifications;

use Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Models\FixtureRecipient;
use Illuminate\Notifications\Notification;

class FixtureNotificationWithModelInput extends Notification
{
    public function __construct(
        public FixtureRecipient $recipient,
        public int $reportId
    ) {}
}
