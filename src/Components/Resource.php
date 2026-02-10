<?php

namespace Laravel\Ranger\Components;

use Laravel\Ranger\Concerns\HasFilePath;
use Laravel\Surveyor\Types\Contracts\Type;

class Resource
{
    use HasFilePath;

    /**
     * @var array<string, Type>
     */
    protected array $fields = [];

    public function __construct(public readonly string $name)
    {
        //
    }

    /**
     * @return array<string, Type>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function addField(string $name, Type $type): void
    {
        $this->fields[$name] = $type;
    }
}
