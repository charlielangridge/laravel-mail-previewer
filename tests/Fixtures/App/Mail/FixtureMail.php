<?php

namespace Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;

class FixtureMail extends Mailable
{
    public function __construct(
        public int $userId,
        public string $token,
        public ?string $optional = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Fixture Mail Subject');
    }

    public function build(): self
    {
        return $this;
    }
}
