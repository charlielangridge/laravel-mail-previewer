<?php

namespace Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Mail;

use Illuminate\Mail\Mailable;

class FixtureRenderableMail extends Mailable
{
    public function __construct(
        public string $name
    ) {}

    public function build(): self
    {
        return $this->subject('Fixture Renderable Mail')
            ->html('<p>Hello '.$this->name.'</p>');
    }
}
