<?php

namespace Charlielangridge\LaravelMailPreviewer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use Throwable;

class LaravelMailPreviewerHtmlRenderer
{
    public function render(string $className, array $parameters = [], mixed $notifiable = null): ?string
    {
        if (! class_exists($className)) {
            return null;
        }

        if (is_subclass_of($className, Mailable::class)) {
            return $this->renderMailable($className, $parameters);
        }

        if (is_subclass_of($className, Notification::class)) {
            return $this->renderNotification($className, $parameters, $notifiable);
        }

        return null;
    }

    protected function renderMailable(string $className, array $parameters): ?string
    {
        try {
            /** @var Mailable $mailable */
            $mailable = $this->instantiateClass($className, $parameters);

            return $this->normalizeRenderedOutput($mailable->render());
        } catch (Throwable) {
            return null;
        }
    }

    protected function renderNotification(string $className, array $parameters, mixed $notifiable): ?string
    {
        try {
            /** @var Notification $notification */
            $notification = $this->instantiateClass($className, $parameters);
        } catch (Throwable) {
            return null;
        }

        $notifiableInstance = $notifiable ?? (new AnonymousNotifiable())->route('mail', 'preview@example.test');

        try {
            $mailRepresentation = $notification->toMail($notifiableInstance);
        } catch (Throwable) {
            return null;
        }

        if ($mailRepresentation instanceof Mailable) {
            try {
                return $this->normalizeRenderedOutput($mailRepresentation->render());
            } catch (Throwable) {
                return null;
            }
        }

        if ($mailRepresentation instanceof MailMessage) {
            try {
                return $this->normalizeRenderedOutput($mailRepresentation->render())
                    ?? $this->renderMailMessageFallback($mailRepresentation);
            } catch (Throwable) {
                return $this->renderMailMessageFallback($mailRepresentation);
            }
        }

        return null;
    }

    protected function instantiateClass(string $className, array $parameters): object
    {
        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            if (array_key_exists($parameter->getName(), $parameters)) {
                $arguments[] = $this->coerceParameterValue($parameter, $parameters[$parameter->getName()]);
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            throw new \InvalidArgumentException('Missing required parameter: '.$parameter->getName());
        }

        return $reflection->newInstanceArgs($arguments);
    }

    protected function coerceParameterValue(ReflectionParameter $parameter, mixed $value): mixed
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return $value;
        }

        $className = $type->getName();

        if (! is_a($className, Model::class, true)) {
            return $value;
        }

        if ($value instanceof $className) {
            return $value;
        }

        if (is_scalar($value)) {
            return $className::query()->findOrFail($value);
        }

        return $value;
    }

    protected function normalizeRenderedOutput(mixed $rendered): ?string
    {
        if (is_string($rendered)) {
            return $rendered;
        }

        if ($rendered instanceof Htmlable) {
            return $rendered->toHtml();
        }

        if (is_object($rendered) && method_exists($rendered, '__toString')) {
            return (string) $rendered;
        }

        return null;
    }

    protected function renderMailMessageFallback(MailMessage $mailMessage): string
    {
        $parts = [];

        if (is_string($mailMessage->subject) && $mailMessage->subject !== '') {
            $parts[] = '<h1>'.e($mailMessage->subject).'</h1>';
        }

        foreach ($mailMessage->introLines as $line) {
            $parts[] = '<p>'.e((string) $line).'</p>';
        }

        foreach ($mailMessage->outroLines as $line) {
            $parts[] = '<p>'.e((string) $line).'</p>';
        }

        return implode("\n", $parts);
    }
}
