<?php

namespace Laravel\Ranger\Collectors;

use Illuminate\Support\Collection;
use Laravel\Ranger\Components\InertiaSharedData as SharedDataComponent;
use Laravel\Ranger\Types\ArrayType;
use Laravel\Ranger\Types\Type;
use Laravel\Ranger\Types\UnionType;
use Laravel\Ranger\Util\Parser;
use Laravel\Ranger\Util\TypeResolver;
use PhpParser\Node;
use ReflectionClass;
use Spatie\StructureDiscoverer\Discover;

class InertiaSharedData extends Collector
{
    protected array $parsed = [];

    public function __construct(
        protected Parser $parser,
        protected TypeResolver $typeResolver,
    ) {
        //
    }

    public function collect(): Collection
    {
        return collect(
            Discover::in(app_path())
                ->classes()
                ->extending('Inertia\\Middleware')
                ->get()
        )->map($this->processSharedData(...));
    }

    // TODO: Figure this out...
    // - `array`
    // - `callable`
    // - `bool`
    // - `float`
    // - `int`
    // - `string`
    // - `iterable`
    // - `object`
    // - `mixed`
    protected function processSharedData(string $path): SharedDataComponent
    {
        $reflected = new ReflectionClass($path);
        $contents = file_get_contents($reflected->getFileName());

        $this->parsed = $this->parser->parse($contents);

        $node = $this->parser->nodeFinder()->findFirst(
            $this->parsed,
            fn ($node) => $node instanceof Node\Stmt\ClassMethod
                && $node->name->name === 'share',
        );

        if (! $node) {
            return new SharedDataComponent(new ArrayType([]));
        }

        $data = $this->typeResolver->setParsed($this->parsed)->from($node);

        if ($data instanceof UnionType) {
            $finalArray = [];

            foreach ($data->types as $type) {
                if ($type instanceof ArrayType) {
                    foreach ($type->value as $key => $value) {
                        $finalArray[$key] ??= [];
                        $finalArray[$key][] = $value;
                    }
                } else {
                    dd('Unexpected type in Inertia shared data: '.get_class($type));
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
