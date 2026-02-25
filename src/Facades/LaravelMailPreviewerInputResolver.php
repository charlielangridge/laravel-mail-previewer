<?php

namespace Charlielangridge\LaravelMailPreviewer\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Charlielangridge\LaravelMailPreviewer\LaravelMailPreviewerInputResolver
 *
 * @method static array for(string $className)
 */
class LaravelMailPreviewerInputResolver extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Charlielangridge\LaravelMailPreviewer\LaravelMailPreviewerInputResolver::class;
    }
}
