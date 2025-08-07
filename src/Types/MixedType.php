<?php

namespace Laravel\Ranger\Types;

class MixedType extends AbstractType implements Contracts\Type
{
    public function id(): string
    {
        return 'mixed';
    }
}
