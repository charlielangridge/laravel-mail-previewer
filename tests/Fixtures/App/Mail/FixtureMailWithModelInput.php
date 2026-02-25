<?php

namespace Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Mail;

use Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Models\FixtureRecipient;
use Illuminate\Mail\Mailable;

class FixtureMailWithModelInput extends Mailable
{
    public function __construct(
        public FixtureRecipient $recipient,
        public string $token
    ) {}

    public function build(): self
    {
        return $this;
    }
}
