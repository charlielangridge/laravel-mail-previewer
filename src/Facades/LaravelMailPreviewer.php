<?php

namespace Charlielangridge\LaravelMailPreviewer\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Charlielangridge\LaravelMailPreviewer\LaravelMailPreviewer
 *
 * @method static array mailables()
 * @method static array notifications()
 * @method static array discover()
 * @method static array inputRequirements(string $className)
 * @method static ?string renderHtml(string $className, array $parameters = [], mixed $notifiable = null)
 * @method static array inputTypeHintingIssues()
 */
class LaravelMailPreviewer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Charlielangridge\LaravelMailPreviewer\LaravelMailPreviewer::class;
    }
}
