<?php

use Charlielangridge\LaravelMailPreviewer\LaravelMailPreviewerInputResolver;
use Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Mail\FixtureMailWithModelInput;
use Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Models\FixtureRecipient;
use Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Notifications\FixtureNotificationWithModelInput;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    app()->useAppPath(__DIR__.'/Fixtures/App');

    Schema::dropIfExists('fixture_recipients');
    Schema::create('fixture_recipients', function ($table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });

    for ($index = 1; $index <= 105; $index++) {
        FixtureRecipient::query()->create([
            'name' => 'Recipient '.$index,
        ]);
    }
});

it('returns required input requirements for a mailable with model options', function () {
    $resolver = app(LaravelMailPreviewerInputResolver::class);

    $requirements = $resolver->for(FixtureMailWithModelInput::class);

    expect($requirements)->toHaveCount(2)
        ->and($requirements[0]['name'])->toBe('recipient')
        ->and($requirements[0]['type'])->toBe(FixtureRecipient::class)
        ->and($requirements[0])->toHaveKey('options')
        ->and($requirements[0]['options'])->toHaveCount(100)
        ->and($requirements[0]['options'][0])->toMatchArray([
            'id' => 1,
            'label' => 'Recipient 1',
        ])
        ->and($requirements[1])->toBe([
            'name' => 'token',
            'type' => 'string',
        ])
        ->and(array_key_exists('options', $requirements[1]))->toBeFalse();
});

it('returns required input requirements for a notification with model options', function () {
    $resolver = app(LaravelMailPreviewerInputResolver::class);

    $requirements = $resolver->for(FixtureNotificationWithModelInput::class);

    expect($requirements)->toHaveCount(2)
        ->and($requirements[0]['name'])->toBe('recipient')
        ->and($requirements[0]['type'])->toBe(FixtureRecipient::class)
        ->and($requirements[0])->toHaveKey('options')
        ->and($requirements[0]['options'])->toHaveCount(100)
        ->and($requirements[1])->toBe([
            'name' => 'reportId',
            'type' => 'int',
        ]);
});

it('returns empty array for unsupported classes', function () {
    $resolver = app(LaravelMailPreviewerInputResolver::class);

    expect($resolver->for(\stdClass::class))->toBe([]);
});
