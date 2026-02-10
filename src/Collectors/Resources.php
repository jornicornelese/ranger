<?php

namespace Laravel\Ranger\Collectors;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;
use Laravel\Ranger\Components\Resource as ResourceComponent;
use Laravel\Surveyor\Analyzer\Analyzer;
use Laravel\Surveyor\Types\ArrayType;
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

        foreach ($returnType->value as $key => $type) {
            $component->addField($key, $type);
        }

        return $component;
    }
}
