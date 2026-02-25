<?php

namespace Charlielangridge\LaravelMailPreviewer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;
use Throwable;

class LaravelMailPreviewer
{
    /**
     * @var array<int, class-string<Model>>|null
     */
    protected ?array $appModelClasses = null;

    /**
     * @return array<int, array{name: string, type: string, options?: array<int, array{id: mixed, label: string}>}>
     */
    public function inputRequirements(string $className): array
    {
        return app(LaravelMailPreviewerInputResolver::class)->for($className);
    }

    public function renderHtml(string $className, array $parameters = [], mixed $notifiable = null): ?string
    {
        return app(LaravelMailPreviewerHtmlRenderer::class)->render($className, $parameters, $notifiable);
    }

    /**
     * @return array{mailables: list<array<string, mixed>>, notifications: list<array<string, mixed>>}
     */
    public function inputTypeHintingIssues(): array
    {
        return [
            'mailables' => $this->collectTypeHintingIssues($this->mailables(), 'mailable'),
            'notifications' => $this->collectTypeHintingIssues($this->notifications(), 'notification'),
        ];
    }

    /**
     * @return array<int, class-string<Mailable>>
     */
    public function mailables(): array
    {
        /** @var array<int, class-string<Mailable>> $mailables */
        $mailables = $this->discoverClassesExtending(Mailable::class);

        return $mailables;
    }

    /**
     * @return array<int, class-string<Notification>>
     */
    public function notifications(): array
    {
        /** @var array<int, class-string<Notification>> $notifications */
        $notifications = $this->discoverClassesExtending(Notification::class);

        return $notifications;
    }

    /**
     * @return array{mailables: list<array<string, mixed>>, notifications: list<array<string, mixed>>}
     */
    public function discover(): array
    {
        return [
            'mailables' => array_map(
                fn (string $class): array => $this->mapDiscoveredClass($class, Mailable::class),
                $this->mailables()
            ),
            'notifications' => array_map(
                fn (string $class): array => $this->mapDiscoveredClass($class, Notification::class),
                $this->notifications()
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapDiscoveredClass(string $className, string $baseClass): array
    {
        $reflection = new ReflectionClass($className);

        return [
            'name' => class_basename($className),
            'class' => $className,
            'subject' => $this->extractSubject($reflection, $baseClass),
            'input_requirements' => $this->extractInputRequirements($reflection),
        ];
    }

    /**
     * @return array<int, class-string>
     */
    protected function discoverClassesExtending(string $baseClass): array
    {
        $classes = [];

        foreach (File::allFiles(app_path()) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            foreach ($this->extractClassesFromFile($file->getRealPath()) as $className) {
                if (! class_exists($className) || ! is_subclass_of($className, $baseClass)) {
                    continue;
                }

                try {
                    if ((new ReflectionClass($className))->isAbstract()) {
                        continue;
                    }
                } catch (Throwable) {
                    continue;
                }

                $classes[] = $className;
            }
        }

        $classes = array_values(array_unique($classes));
        sort($classes);

        return $classes;
    }

    /**
     * @return array<int, class-string>
     */
    protected function extractClassesFromFile(string $path): array
    {
        $content = file_get_contents($path);

        if (! is_string($content) || $content === '') {
            return [];
        }

        $tokens = token_get_all($content);
        $namespace = '';
        $classes = [];

        $tokenCount = count($tokens);

        for ($index = 0; $index < $tokenCount; $index++) {
            $token = $tokens[$index];

            if (! is_array($token)) {
                continue;
            }

            if ($token[0] === T_NAMESPACE) {
                $namespace = $this->parseNamespace($tokens, $index);

                continue;
            }

            if ($token[0] !== T_CLASS) {
                continue;
            }

            $previous = $this->previousMeaningfulToken($tokens, $index);

            if (is_array($previous) && $previous[0] === T_NEW) {
                continue; // anonymous class
            }

            $className = $this->nextNamedToken($tokens, $index);

            if ($className === null) {
                continue;
            }

            $classes[] = $namespace !== '' ? $namespace.'\\'.$className : $className;
        }

        return $classes;
    }

    protected function parseNamespace(array $tokens, int &$index): string
    {
        $namespace = '';
        $tokenCount = count($tokens);

        for ($cursor = $index + 1; $cursor < $tokenCount; $cursor++) {
            $token = $tokens[$cursor];

            if (is_string($token)) {
                if ($token === ';' || $token === '{') {
                    $index = $cursor;

                    break;
                }

                continue;
            }

            if (in_array($token[0], [T_STRING, T_NS_SEPARATOR, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                $namespace .= $token[1];
            }
        }

        return $namespace;
    }

    protected function previousMeaningfulToken(array $tokens, int $index): mixed
    {
        for ($cursor = $index - 1; $cursor >= 0; $cursor--) {
            $token = $tokens[$cursor];

            if (is_string($token)) {
                if (trim($token) === '') {
                    continue;
                }

                return $token;
            }

            if (in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return $token;
        }

        return null;
    }

    protected function nextNamedToken(array $tokens, int &$index): ?string
    {
        $tokenCount = count($tokens);

        for ($cursor = $index + 1; $cursor < $tokenCount; $cursor++) {
            $token = $tokens[$cursor];

            if (is_array($token) && $token[0] === T_STRING) {
                $index = $cursor;

                return $token[1];
            }

            if (is_string($token) && $token === '{') {
                return null;
            }
        }

        return null;
    }

    protected function extractSubject(ReflectionClass $reflection, string $baseClass): ?string
    {
        if ($baseClass === Mailable::class) {
            return $this->extractMailableSubject($reflection);
        }

        if ($baseClass === Notification::class) {
            return $this->extractNotificationSubject($reflection);
        }

        return null;
    }

    protected function extractMailableSubject(ReflectionClass $reflection): ?string
    {
        $subject = $this->extractDefaultSubjectProperty($reflection);

        if ($subject !== null) {
            return $subject;
        }

        return $this->extractSubjectFromSource($reflection);
    }

    protected function extractNotificationSubject(ReflectionClass $reflection): ?string
    {
        $subject = $this->extractDefaultSubjectProperty($reflection);

        if ($subject !== null) {
            return $subject;
        }

        return $this->extractSubjectFromSource($reflection);
    }

    protected function extractDefaultSubjectProperty(ReflectionClass $reflection): ?string
    {
        try {
            if (! $reflection->hasProperty('subject')) {
                return null;
            }

            $property = $reflection->getProperty('subject');
            $defaults = $reflection->getDefaultProperties();
            $name = $property->getName();

            if (! array_key_exists($name, $defaults)) {
                return null;
            }

            $subject = $defaults[$name];

            return is_string($subject) && $subject !== '' ? $subject : null;
        } catch (ReflectionException) {
            return null;
        }
    }

    protected function extractSubjectFromSource(ReflectionClass $reflection): ?string
    {
        $path = $reflection->getFileName();

        if ($path === false) {
            return null;
        }

        $source = @file_get_contents($path);

        if (! is_string($source) || $source === '') {
            return null;
        }

        foreach ($this->extractSubjectCallCandidates($source) as $candidate) {
            $expression = $candidate['expression'];
            $offset = $candidate['offset'];
            $context = $this->extractMethodContext($source, $offset);

            $normalized = $this->resolveSubjectExpression(
                $expression,
                $context['body'],
                $context['relative_offset']
            );

            if ($normalized !== null) {
                return $normalized;
            }
        }

        return null;
    }

    protected function normalizeSubjectText(string $subject): ?string
    {
        $subject = preg_replace('/\{\s*\$this->([^}]+)\s*}/', '**$1**', $subject) ?? $subject;
        $subject = preg_replace('/\$this->([a-zA-Z_][\w]*(?:->[\w]+)*)/', '**$1**', $subject) ?? $subject;
        $subject = trim((string) preg_replace('/\s+/', ' ', $subject));

        return $subject !== '' ? $subject : null;
    }

    protected function normalizeSubjectExpression(string $expression): ?string
    {
        $normalized = trim($expression);
        $normalized = preg_replace('/\s*\.\s*/', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/([\'"])(.*?)\1/s', '$2', $normalized) ?? $normalized;
        $normalized = $this->normalizeSubjectText($normalized) ?? '';
        $normalized = trim((string) preg_replace('/\s+/', ' ', $normalized));

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * @return array<int, array{expression: string, offset: int}>
     */
    protected function extractSubjectCallCandidates(string $source): array
    {
        $candidates = [];
        $patterns = [
            '/->subject\(\s*(.*?)\s*\)/s',
            '/subject\s*:\s*(.*?)(?:,|\))/s',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $source, $matches, PREG_OFFSET_CAPTURE) !== false) {
                foreach ($matches[1] as $match) {
                    $expression = $match[0];
                    $offset = $match[1];

                    $candidates[] = [
                        'expression' => $expression,
                        'offset' => $offset,
                    ];
                }
            }
        }

        usort($candidates, fn (array $a, array $b): int => $a['offset'] <=> $b['offset']);

        return $candidates;
    }

    /**
     * @return array{body: string, relative_offset: int}
     */
    protected function extractMethodContext(string $source, int $offset): array
    {
        $before = substr($source, 0, $offset);
        $functionStart = strrpos($before, 'function ');

        if ($functionStart === false) {
            return ['body' => $source, 'relative_offset' => $offset];
        }

        $openBrace = strpos($source, '{', $functionStart);

        if ($openBrace === false) {
            return ['body' => $source, 'relative_offset' => $offset];
        }

        $depth = 0;
        $length = strlen($source);
        $end = $length - 1;

        for ($index = $openBrace; $index < $length; $index++) {
            $char = $source[$index];

            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;

                if ($depth === 0) {
                    $end = $index;
                    break;
                }
            }
        }

        $body = substr($source, $functionStart, ($end - $functionStart) + 1);
        $relativeOffset = max(0, $offset - $functionStart);

        return ['body' => $body, 'relative_offset' => $relativeOffset];
    }

    protected function resolveSubjectExpression(
        string $expression,
        string $contextBody,
        int $contextOffset,
        int $depth = 0
    ): ?string {
        if ($depth > 5) {
            return null;
        }

        $expression = trim($expression);

        if ($expression === '') {
            return null;
        }

        if ($this->isQuotedLiteral($expression)) {
            return $this->normalizeSubjectText($this->stripQuotes($expression));
        }

        if (preg_match('/^\$[a-zA-Z_]\w*$/', $expression) === 1) {
            $resolved = $this->resolveVariableAssignment($expression, $contextBody, $contextOffset);

            if ($resolved === null) {
                return null;
            }

            return $this->resolveSubjectExpression($resolved, $contextBody, $contextOffset, $depth + 1);
        }

        $parts = preg_split('/\s*\.\s*/', $expression) ?: [];

        if (count($parts) > 1) {
            $resolvedParts = [];

            foreach ($parts as $part) {
                $part = trim($part);

                if ($part === '') {
                    continue;
                }

                $resolved = $this->resolveSubjectExpression($part, $contextBody, $contextOffset, $depth + 1);

                if ($resolved !== null) {
                    $resolvedParts[] = $resolved;
                }
            }

            $combined = trim(implode(' ', $resolvedParts));

            return $combined !== '' ? $combined : null;
        }

        return $this->normalizeSubjectExpression($expression);
    }

    protected function resolveVariableAssignment(string $variable, string $contextBody, int $contextOffset): ?string
    {
        $prefix = substr($contextBody, 0, $contextOffset);
        $variable = preg_quote($variable, '/');

        if (preg_match_all('/'.$variable.'\s*=\s*(.*?);/s', $prefix, $matches) !== false) {
            $assignments = $matches[1] ?? [];
            $last = end($assignments);

            return is_string($last) ? trim($last) : null;
        }

        return null;
    }

    protected function isQuotedLiteral(string $value): bool
    {
        return preg_match('/^([\'"]).*\1$/s', $value) === 1;
    }

    protected function stripQuotes(string $value): string
    {
        return substr($value, 1, -1);
    }

    /**
     * @return array<int, array{name: string, type: string}>
     */
    protected function extractInputRequirements(ReflectionClass $reflection): array
    {
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return [];
        }

        $requirements = [];

        foreach ($constructor->getParameters() as $parameter) {
            if ($parameter->isOptional()) {
                continue;
            }

            $requirements[] = [
                'name' => $parameter->getName(),
                'type' => $this->normalizeParameterType($parameter),
            ];
        }

        return $requirements;
    }

    protected function normalizeParameterType(ReflectionParameter $parameter): string
    {
        $type = $parameter->getType();

        if ($type === null) {
            return 'mixed';
        }

        return $this->normalizeType($type);
    }

    protected function normalizeType(ReflectionType $type): string
    {
        if ($type instanceof ReflectionNamedType) {
            return $type->getName();
        }

        if ($type instanceof ReflectionUnionType) {
            return implode('|', array_map(
                fn (ReflectionType $unionType): string => $this->normalizeType($unionType),
                $type->getTypes()
            ));
        }

        return 'mixed';
    }

    /**
     * @param  array<int, class-string>  $classes
     * @return list<array<string, mixed>>
     */
    protected function collectTypeHintingIssues(array $classes, string $kind): array
    {
        $issues = [];

        foreach ($classes as $className) {
            $reflection = new ReflectionClass($className);

            $constructor = $reflection->getConstructor();

            if ($constructor === null) {
                continue;
            }

            $untypedInputs = [];

            foreach ($constructor->getParameters() as $parameter) {
                if ($parameter->isOptional()) {
                    continue;
                }

                $normalizedType = $this->normalizeParameterType($parameter);
                $hasTypeHint = $parameter->getType() !== null;

                if ($hasTypeHint && $normalizedType !== 'mixed') {
                    continue;
                }

                $suggestion = $this->suggestTypeForParameter($parameter);

                $untypedInputs[] = [
                    'name' => $parameter->getName(),
                    'current_type' => $normalizedType,
                    'has_type_hint' => $hasTypeHint,
                    'suggested_type' => $suggestion['type'],
                    'suggestion_reason' => $suggestion['reason'],
                ];
            }

            if ($untypedInputs === []) {
                continue;
            }

            $issues[] = [
                'kind' => $kind,
                'name' => class_basename($className),
                'class' => $className,
                'untyped_input_requirements' => $untypedInputs,
            ];
        }

        return $issues;
    }

    /**
     * @return array{type: string, reason: string}
     */
    protected function suggestTypeForParameter(ReflectionParameter $parameter): array
    {
        if ($parameter->isDefaultValueAvailable()) {
            $default = $parameter->getDefaultValue();

            if (is_int($default)) {
                return ['type' => 'int', 'reason' => 'Default value is an integer'];
            }

            if (is_string($default)) {
                return ['type' => 'string', 'reason' => 'Default value is a string'];
            }

            if (is_bool($default)) {
                return ['type' => 'bool', 'reason' => 'Default value is a boolean'];
            }

            if (is_float($default)) {
                return ['type' => 'float', 'reason' => 'Default value is a float'];
            }

            if (is_array($default)) {
                return ['type' => 'array', 'reason' => 'Default value is an array'];
            }
        }

        $parameterName = $parameter->getName();

        $modelSuggestion = $this->suggestModelTypeFromParameterName($parameterName);

        if ($modelSuggestion !== null) {
            return [
                'type' => $modelSuggestion,
                'reason' => 'Parameter name matches an app model class name',
            ];
        }

        if (preg_match('/^(?:is|has|can|should)(?:[A-Z_]|_)/', $parameterName) === 1) {
            return ['type' => 'bool', 'reason' => 'Parameter name suggests a boolean flag'];
        }

        if (preg_match('/(?:id|count|total|qty|quantity|index|offset|position|number)$/i', $parameterName) === 1) {
            return ['type' => 'int', 'reason' => 'Parameter name suggests a numeric identifier or counter'];
        }

        if (preg_match('/(?:email|name|title|subject|token|slug|url|path|message|body|content)$/i', $parameterName) === 1) {
            return ['type' => 'string', 'reason' => 'Parameter name suggests text data'];
        }

        if (preg_match('/(?:date|time|at|from|to)$/i', $parameterName) === 1) {
            return ['type' => '\DateTimeInterface', 'reason' => 'Parameter name suggests a date/time value'];
        }

        return ['type' => 'string', 'reason' => 'Fallback suggestion for untyped input'];
    }

    protected function suggestModelTypeFromParameterName(string $parameterName): ?string
    {
        $needle = strtolower($parameterName);

        foreach ($this->appModelClasses() as $modelClass) {
            $baseName = class_basename($modelClass);
            $normalizedBaseName = strtolower($baseName);

            if ($normalizedBaseName === $needle) {
                return $modelClass;
            }

            if (str_ends_with($normalizedBaseName, $needle)) {
                return $modelClass;
            }
        }

        return null;
    }

    /**
     * @return array<int, class-string<Model>>
     */
    protected function appModelClasses(): array
    {
        if ($this->appModelClasses !== null) {
            return $this->appModelClasses;
        }

        if (! class_exists(Model::class)) {
            $this->appModelClasses = [];

            return $this->appModelClasses;
        }

        $models = [];

        foreach ($this->discoverClassesExtending(Model::class) as $className) {
            if (is_a($className, Model::class, true)) {
                /** @var class-string<Model> $className */
                $models[] = $className;
            }
        }

        $this->appModelClasses = $models;

        return $this->appModelClasses;
    }
}
