<?php

namespace Charlielangridge\LaravelMailPreviewer\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Charlielangridge\LaravelMailPreviewer\LaravelMailPreviewer
 */
class LaravelMailPreviewer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Charlielangridge\LaravelMailPreviewer\LaravelMailPreviewer::class;
    }
}
