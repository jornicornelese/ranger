<?php

namespace Laravel\Ranger\Collectors;

use Illuminate\Database\Eloquent\ModelInspector;
use Illuminate\Support\Collection;
use Laravel\Ranger\Components\Enum as EnumComponent;
use ReflectionClass;
use Spatie\StructureDiscoverer\Discover;

class Enums extends Collector
{
    public function __construct(protected ModelInspector $inspector)
    {
        //
    }

    public function collect(): Collection
    {
        return collect(Discover::in(app_path())->enums()->get())->map($this->toComponent(...));
    }

    protected function toComponent(string $enum): EnumComponent
    {
        $cases = collect($enum::cases())->mapWithKeys(
            fn ($case) => [$case->name => $case->value]
        )->all();

        $component = new EnumComponent($enum, $cases);
        $component->setFilePath((new ReflectionClass($enum))->getFileName());

        return $component;
    }
}
