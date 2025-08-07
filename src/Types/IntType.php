<?php

namespace Laravel\Ranger\Types;

class IntType extends AbstractType implements Contracts\Type
{
    public function __construct(public readonly ?int $value = null)
    {
        //
    }

    public function id(): string
    {
        return $this->value === null ? 'null' : (string) $this->value;
    }
}
