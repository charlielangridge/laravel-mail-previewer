<?php

namespace Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Mail;

use Illuminate\Mail\Mailable;

class FixtureUntypedMail extends Mailable
{
    public function __construct(
        public $recipient,
        public $token,
        public $isResend
    ) {}

    public function build(): self
    {
        return $this->subject('Untyped Mail');
    }
}
