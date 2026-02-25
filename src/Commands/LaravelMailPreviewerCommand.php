<?php

namespace Charlielangridge\LaravelMailPreviewer\Commands;

use Illuminate\Console\Command;

class LaravelMailPreviewerCommand extends Command
{
    public $signature = 'laravel-mail-previewer';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
