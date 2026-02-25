<?php

namespace Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Mail;

use Illuminate\Mail\Mailable;

abstract class AbstractFixtureMail extends Mailable
{
    public function build(): self
    {
        return $this;
    }
}
