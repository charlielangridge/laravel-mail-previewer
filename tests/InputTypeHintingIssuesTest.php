<?php

use Charlielangridge\LaravelMailPreviewer\LaravelMailPreviewer;
use Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Mail\FixtureUntypedMail;
use Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Models\FixtureRecipient;
use Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Notifications\FixtureUntypedNotification;

it('returns mailables and notifications with untyped required constructor inputs and suggestions', function () {
    app()->useAppPath(__DIR__.'/Fixtures/App');

    $previewer = app(LaravelMailPreviewer::class);
    $issues = $previewer->inputTypeHintingIssues();
    $mailableIssue = collect($issues['mailables'])->firstWhere('class', FixtureUntypedMail::class);
    $notificationIssue = collect($issues['notifications'])->firstWhere('class', FixtureUntypedNotification::class);
    $mailableInputs = collect($mailableIssue['untyped_input_requirements'] ?? [])->keyBy('name');
    $notificationInputs = collect($notificationIssue['untyped_input_requirements'] ?? [])->keyBy('name');

    expect($issues)
        ->toHaveKeys(['mailables', 'notifications'])
        ->and($mailableIssue)->not->toBeNull()
        ->and($mailableIssue['kind'])->toBe('mailable')
        ->and($mailableInputs->get('recipient')['suggested_type'])->toBe(FixtureRecipient::class)
        ->and($mailableInputs->get('token')['suggested_type'])->toBe('string')
        ->and($mailableInputs->get('isResend')['suggested_type'])->toBe('bool')
        ->and($notificationIssue)->not->toBeNull()
        ->and($notificationIssue['kind'])->toBe('notification')
        ->and($notificationInputs->get('recipientId')['suggested_type'])->toBe('int')
        ->and($notificationInputs->get('payload')['current_type'])->toBe('mixed')
        ->and($notificationInputs->get('payload')['has_type_hint'])->toBeTrue();
});
