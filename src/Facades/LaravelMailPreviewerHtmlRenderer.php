<?php

namespace Charlielangridge\LaravelMailPreviewer\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Charlielangridge\LaravelMailPreviewer\LaravelMailPreviewerHtmlRenderer
 *
 * @method static ?string render(string $className, array $parameters = [], mixed $notifiable = null)
 */
class LaravelMailPreviewerHtmlRenderer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Charlielangridge\LaravelMailPreviewer\LaravelMailPreviewerHtmlRenderer::class;
    }
}
