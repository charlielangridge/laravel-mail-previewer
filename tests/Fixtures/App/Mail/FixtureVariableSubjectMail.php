<?php

namespace Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;

class FixtureVariableSubjectMail extends Mailable
{
    public function envelope(): Envelope
    {
        $subject = 'Test';

        return new Envelope(subject: $subject);
    }

    public function build(): self
    {
        return $this;
    }
}
