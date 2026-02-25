<?php

use Charlielangridge\LaravelMailPreviewer\LaravelMailPreviewerHtmlRenderer;
use Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Mail\FixtureRenderableMail;
use Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Models\FixtureRecipient;
use Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Notifications\FixtureRenderableNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
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

it('renders mailable html without sending mail', function () {
    Mail::fake();
    Notification::fake();

    $renderer = app(LaravelMailPreviewerHtmlRenderer::class);

    $html = $renderer->render(FixtureRenderableMail::class, [
        'name' => 'Charlie',
    ]);

    expect($html)->toContain('Hello Charlie');

    Mail::assertNothingSent();
    Notification::assertNothingSent();
});

it('renders notification html without sending mail', function () {
    Mail::fake();
    Notification::fake();

    $recipient = FixtureRecipient::query()->create([
        'name' => 'Taylor',
    ]);

    $renderer = app(LaravelMailPreviewerHtmlRenderer::class);

    $html = $renderer->render(FixtureRenderableNotification::class, [
        'recipient' => $recipient->id,
    ]);

    expect($html)->toContain('Renderable Notification')
        ->toContain('Hello Taylor');

    Mail::assertNothingSent();
    Notification::assertNothingSent();
});
