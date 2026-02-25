<?php

namespace Charlielangridge\LaravelMailPreviewer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;
use Throwable;

class LaravelMailPreviewerInputResolver
{
    /**
     * @return array<int, array{name: string, type: string, options?: array<int, array{id: mixed, label: string}>}>
     */
    public function for(string $className): array
    {
        if (! class_exists($className)) {
            return [];
        }

        if (! is_subclass_of($className, Mailable::class) && ! is_subclass_of($className, Notification::class)) {
            return [];
        }

        try {
            $reflection = new ReflectionClass($className);
            $constructor = $reflection->getConstructor();
        } catch (Throwable) {
            return [];
        }

        if ($constructor === null) {
            return [];
        }

        $requirements = [];

        foreach ($constructor->getParameters() as $parameter) {
            if ($parameter->isOptional()) {
                continue;
            }

            $typeName = $this->normalizeParameterType($parameter);
            $requirement = [
                'name' => $parameter->getName(),
                'type' => $typeName,
            ];

            if ($this->isModelType($parameter)) {
                $requirement['options'] = $this->loadModelOptions($typeName);
            }

            $requirements[] = $requirement;
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

    protected function isModelType(ReflectionParameter $parameter): bool
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return false;
        }

        $className = $type->getName();

        return is_a($className, Model::class, true);
    }

    /**
     * @return array<int, array{id: mixed, label: string}>
     */
    protected function loadModelOptions(string $modelClass): array
    {
        if (! class_exists($modelClass) || ! is_a($modelClass, Model::class, true)) {
            return [];
        }

        try {
            /** @var Model $model */
            $model = app()->make($modelClass);
            $query = $modelClass::query()->limit(100)->get();
        } catch (Throwable) {
            return [];
        }

        $keyName = $model->getKeyName();

        return $query->map(function (Model $row) use ($keyName): array {
            return [
                'id' => $row->getAttribute($keyName),
                'label' => $this->buildOptionLabel($row),
            ];
        })->values()->all();
    }

    protected function buildOptionLabel(Model $model): string
    {
        foreach (['name', 'title', 'email'] as $attribute) {
            $value = $model->getAttribute($attribute);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        $key = $model->getKey();

        return is_scalar($key) ? (string) $key : class_basename($model::class);
    }
}
