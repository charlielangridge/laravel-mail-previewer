<?php

use Charlielangridge\LaravelMailPreviewer\LaravelMailPreviewer;
use Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Mail\FixtureRenderableMail;
use Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Mail\FixtureMailWithModelInput;
use Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Models\FixtureRecipient;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    app()->useAppPath(__DIR__.'/Fixtures/App');

    Schema::dropIfExists('fixture_recipients');
    Schema::create('fixture_recipients', function ($table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });
});

it('provides input requirements and html rendering from the main api', function () {
    $recipient = FixtureRecipient::query()->create([
        'name' => 'Sam',
    ]);

    $previewer = app(LaravelMailPreviewer::class);

    $requirements = $previewer->inputRequirements(FixtureMailWithModelInput::class);
    $html = $previewer->renderHtml(FixtureRenderableMail::class, [
        'name' => 'Sam',
    ]);

    expect($requirements[0]['name'])->toBe('recipient')
        ->and($requirements[0])->toHaveKey('options')
        ->and($requirements[0]['options'])->toContain([
            'id' => $recipient->id,
            'label' => 'Sam',
        ])
        ->and($html)->toContain('Hello Sam');
});
