<?php

namespace Laravel\Ranger\Collectors;

use Illuminate\Support\Collection;
use Laravel\Ranger\Components\InertiaSharedData as SharedDataComponent;
use Laravel\Surveyor\Analyzer\Analyzer;
use Laravel\Surveyor\Types\ArrayType;
use Laravel\Surveyor\Types\Type;
use Laravel\Surveyor\Types\UnionType;
use Spatie\StructureDiscoverer\Discover;

class InertiaSharedData extends Collector
{
    public function __construct(protected Analyzer $analyzer)
    {
        //
    }

    /**
     * @return Collection<SharedDataComponent>
     */
    public function collect(): Collection
    {
        $discovered = Discover::in(app_path())
            ->classes()
            ->extending('Inertia\\Middleware')
            ->get();

        return collect($discovered)->map($this->processSharedData(...));
    }

    /**
     * @param  class-string<\Inertia\Middleware>  $class
     */
    protected function processSharedData(string $class): SharedDataComponent
    {
        $result = $this->analyzer->analyzeClass($class)->result();

        if (! $result->hasMethod('share')) {
            return new SharedDataComponent(new ArrayType([]));
        }

        $data = $result->getMethod('share')->returnType();

        if ($data instanceof UnionType) {
            $finalArray = [];

            foreach ($data->types as $type) {
                if ($type instanceof ArrayType) {
                    foreach ($type->value as $key => $value) {
                        $finalArray[$key] ??= [];
                        $finalArray[$key][] = $value;
                    }
                }
            }

            foreach ($finalArray as $key => $values) {
                $finalArray[$key] = Type::union(...$values);
            }

            $data = new ArrayType($finalArray);
        }

        return new SharedDataComponent($data);
    }
}
