# Laravel Mail Previewer

[![Latest Version on Packagist](https://img.shields.io/packagist/v/charlielangridge/laravel-mail-previewer.svg?style=flat-square)](https://packagist.org/packages/charlielangridge/laravel-mail-previewer)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/charlielangridge/laravel-mail-previewer/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/charlielangridge/laravel-mail-previewer/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/charlielangridge/laravel-mail-previewer.svg?style=flat-square)](https://packagist.org/packages/charlielangridge/laravel-mail-previewer)

Discover mailables and notifications in a Laravel app, inspect required inputs, and render preview HTML safely.

This package is designed for internal preview tooling and admin UIs.

## Features

- Discover all app mailables and notifications.
- Return structured metadata:
  - short `name`
  - full `class`
  - `subject` (including dynamic placeholder parsing)
  - `input_requirements`
- Resolve required constructor input for a selected class.
- For model-typed inputs, return up to 100 selectable DB options.
- Render HTML previews for mailables and notifications.
- Never sends email when rendering previews.

## Requirements

- PHP `^8.4`
- PHP `^8.3`
- Laravel `^11.0 || ^12.0`

## Installation

```bash
composer require charlielangridge/laravel-mail-previewer
```

The package auto-registers via Laravel package discovery.

## Quick Start

```php
use Charlielangridge\LaravelMailPreviewer\Facades\LaravelMailPreviewer;

$list = LaravelMailPreviewer::discover();

$requirements = LaravelMailPreviewer::inputRequirements(
    \App\Mail\FormCompletion::class
);

$issues = LaravelMailPreviewer::inputTypeHintingIssues();

$html = LaravelMailPreviewer::renderHtml(
    \App\Mail\FormCompletion::class,
    ['form' => 12]
);
```

## API

### `LaravelMailPreviewer::discover(): array`

Returns both mailables and notifications:

```php
[
    'mailables' => [
        [
            'name' => 'FormCompletion',
            'class' => 'App\\Mail\\FormCompletion',
            'subject' => 'Hello **user->name**',
            'input_requirements' => [
                ['name' => 'form', 'type' => 'App\\Models\\Forms\\Form'],
            ],
        ],
    ],
    'notifications' => [
        // same shape
    ],
]
```

Subject extraction rules:

- Uses class-level default `subject` when available.
- Parses common subject definitions from class source:
  - `Envelope(subject: ...)`
  - `->subject(...)`
- Resolves local variables where possible:
  - `$subject = 'Test'; ->subject($subject)` becomes `Test`
- External/runtime references are converted to placeholders:
  - `'Hello '.$this->user->name` becomes `Hello **user->name**`

### `LaravelMailPreviewer::inputRequirements(string $className): array`

Returns required constructor parameters for a mailable/notification.

If parameter type is an Eloquent model, includes `options` from DB (limit 100):

```php
[
    [
        'name' => 'form',
        'type' => 'App\\Models\\Forms\\Form',
        'options' => [
            ['id' => 12, 'label' => 'Access Training Feedback'],
            // ...
        ],
    ],
    [
        'name' => 'token',
        'type' => 'string',
    ],
]
```

Notes:

- Non-model parameters do not include `options`.
- Unsupported classes return an empty array.

### `LaravelMailPreviewer::inputTypeHintingIssues(): array`

Returns mailables/notifications where required constructor inputs are not properly type-hinted
(missing type hint or resolved as `mixed`), so you can work through and fix them.

```php
[
    'mailables' => [
        [
            'kind' => 'mailable',
            'name' => 'FormCompletion',
            'class' => 'App\\Mail\\FormCompletion',
            'untyped_input_requirements' => [
                [
                    'name' => 'form',
                    'current_type' => 'mixed',
                    'has_type_hint' => false,
                    'suggested_type' => 'App\\Models\\Forms\\Form',
                    'suggestion_reason' => 'parameter name matches an app model class name',
                ],
            ],
        ],
    ],
    'notifications' => [
        // same shape
    ],
]
```

Suggestion strategy:

- Uses default parameter value type when present.
- Attempts to map parameter names to app model classes.
- Applies name heuristics (`...Id` => `int`, `is...` => `bool`, `token/email/name/...` => `string`).
- Falls back to `string`.

### `LaravelMailPreviewer::renderHtml(string $className, array $parameters = [], mixed $notifiable = null): ?string`

Renders HTML preview for a mailable or notification using Laravel's rendering pipeline.

```php
$html = LaravelMailPreviewer::renderHtml(
    \App\Notifications\FormCompleted::class,
    ['form' => 12]
);
```

For model-typed constructor inputs, scalar values are treated as primary keys and resolved via `findOrFail`.

`$notifiable` is optional for notifications. If omitted, an internal anonymous notifiable is used.

Important:

- This method does not call send/notify pathways.
- It is for preview rendering only.

## Tinker Examples

```php
use Charlielangridge\LaravelMailPreviewer\Facades\LaravelMailPreviewer;

// 1) list discoverable items
LaravelMailPreviewer::discover();

// 2) get inputs for chosen class
LaravelMailPreviewer::inputRequirements(\App\Mail\FormCompletion::class);

// 3) audit classes with weak constructor type hints
LaravelMailPreviewer::inputTypeHintingIssues();

// 4) render html preview with selected values
$html = LaravelMailPreviewer::renderHtml(
    \App\Mail\FormCompletion::class,
    ['form' => 12]
);

$html;
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for details.

## License

MIT. See [LICENSE.md](LICENSE.md).
