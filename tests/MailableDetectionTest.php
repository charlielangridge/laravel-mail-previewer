<?php

use Charlielangridge\LaravelMailPreviewer\LaravelMailPreviewer;
use Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Mail\AbstractFixtureMail;
use Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Mail\FixtureMail;
use Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Mail\FixtureVariableSubjectMail;
use Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Notifications\FixtureDynamicSubjectNotification;
use Charlielangridge\LaravelMailPreviewer\Tests\Fixtures\App\Notifications\FixtureNotification;

it('discovers all mailables in the app path', function () {
    app()->useAppPath(__DIR__.'/Fixtures/App');

    $previewer = app(LaravelMailPreviewer::class);

    expect($previewer->mailables())
        ->toContain(FixtureMail::class)
        ->not->toContain(AbstractFixtureMail::class);
});

it('discovers all notifications in the app path', function () {
    app()->useAppPath(__DIR__.'/Fixtures/App');

    $previewer = app(LaravelMailPreviewer::class);

    expect($previewer->notifications())->toContain(FixtureNotification::class);
});

it('returns both mailables and notifications in discover', function () {
    app()->useAppPath(__DIR__.'/Fixtures/App');

    $previewer = app(LaravelMailPreviewer::class);
    $discovered = $previewer->discover();

    expect($discovered)
        ->toHaveKeys(['mailables', 'notifications'])
        ->and($discovered['mailables'])->toContain([
            'name' => 'FixtureMail',
            'class' => FixtureMail::class,
            'subject' => 'Fixture Mail Subject',
            'input_requirements' => [
                ['name' => 'userId', 'type' => 'int'],
                ['name' => 'token', 'type' => 'string'],
            ],
        ])->toContain([
            'name' => 'FixtureVariableSubjectMail',
            'class' => FixtureVariableSubjectMail::class,
            'subject' => 'Test',
            'input_requirements' => [],
        ])
        ->and($discovered['notifications'])->toContain([
            'name' => 'FixtureNotification',
            'class' => FixtureNotification::class,
            'subject' => 'Fixture Notification Subject',
            'input_requirements' => [
                ['name' => 'accountId', 'type' => 'int'],
                ['name' => 'email', 'type' => 'string'],
            ],
        ])->toContain([
            'name' => 'FixtureDynamicSubjectNotification',
            'class' => FixtureDynamicSubjectNotification::class,
            'subject' => 'Hello **user->name**',
            'input_requirements' => [
                ['name' => 'user', 'type' => 'object'],
            ],
        ]);
});
