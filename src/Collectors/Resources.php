<?php

namespace Laravel\Ranger\Collectors;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;
use Laravel\Ranger\Components\Resource as ResourceComponent;
use Laravel\Surveyor\Analyzer\Analyzer;
use Laravel\Surveyor\Types\ArrayType;
use Laravel\Surveyor\Types\MixedType;
use ReflectionClass;
use Spatie\StructureDiscoverer\Discover;

class Resources extends Collector
{
    public function __construct(protected Analyzer $analyzer)
    {
        //
    }

    /**
     * @return Collection<ResourceComponent>
     */
    public function collect(): Collection
    {
        $discovered = Discover::in(...$this->appPaths)
            ->classes()
            ->extending(JsonResource::class)
            ->get();

        return collect($discovered)
            ->reject(fn (string $class) => is_subclass_of($class, ResourceCollection::class))
            ->map(fn (string $class) => $this->toComponent($class))
            ->filter()
            ->values();
    }

    /**
     * @param  class-string<JsonResource>  $class
     */
    protected function toComponent(string $class): ?ResourceComponent
    {
        $result = $this->analyzer->analyzeClass($class)->result();

        if ($result === null) {
            return null;
        }

        $toArrayMethod = collect($result->publicMethods())
            ->first(fn ($method) => $method->name() === 'toArray');

        if ($toArrayMethod === null) {
            return null;
        }

        $returnType = $toArrayMethod->returnType();

        if (! $returnType instanceof ArrayType) {
            return null;
        }

        $component = new ResourceComponent($class);
        $component->setFilePath($result->filePath());

        $mixinProperties = $this->resolveMixinProperties($class);

        foreach ($returnType->value as $key => $type) {
            if ($type instanceof MixedType && isset($mixinProperties[$key])) {
                $type = $mixinProperties[$key];
            }

            $component->addField($key, $type);
        }

        return $component;
    }

    /**
     * Resolve property types from a @mixin class docblock annotation.
     *
     * @param  class-string  $class
     * @return array<string, \Laravel\Surveyor\Types\Contracts\Type>
     */
    protected function resolveMixinProperties(string $class): array
    {
        $reflection = new ReflectionClass($class);
        $docComment = $reflection->getDocComment();

        if ($docComment === false) {
            return [];
        }

        if (! preg_match('/@mixin\s+\\\\?([\w\\\\]+)/', $docComment, $matches)) {
            return [];
        }

        $mixinClass = $this->resolveClassName($matches[1], $reflection);

        if ($mixinClass === null || ! class_exists($mixinClass)) {
            return [];
        }

        $scope = $this->analyzer->analyzeClass($mixinClass)->analyzed();

        if ($scope === null) {
            return [];
        }

        $properties = [];

        foreach ($scope->state()->properties()->variables() as $name => $states) {
            $last = end($states);

            if ($last && $last->type() !== null) {
                $properties[$name] = $last->type();
            }
        }

        return $properties;
    }

    /**
     * Resolve a short class name to its fully qualified name using the file's use statements.
     */
    protected function resolveClassName(string $name, ReflectionClass $reflection): ?string
    {
        // Already fully qualified
        if (class_exists($name)) {
            return $name;
        }

        // Parse use statements from the source file
        $source = file_get_contents($reflection->getFileName());
        $tokens = token_get_all($source);

        for ($i = 0; $i < count($tokens); $i++) {
            if (! is_array($tokens[$i]) || $tokens[$i][0] !== T_USE) {
                continue;
            }

            $use = '';

            for ($j = $i + 1; $j < count($tokens); $j++) {
                if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_STRING])) {
                    $use .= $tokens[$j][1];
                } elseif ($tokens[$j] === ';') {
                    break;
                }
            }

            $shortName = class_basename($use);

            if ($shortName === $name && class_exists($use)) {
                return $use;
            }
        }

        // Try same namespace as the resource class
        $sameNamespace = $reflection->getNamespaceName().'\\'.$name;

        if (class_exists($sameNamespace)) {
            return $sameNamespace;
        }

        return null;
    }
}
