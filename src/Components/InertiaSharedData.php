<?php

namespace Laravel\Ranger\Components;

use Laravel\Ranger\Types\ArrayType;

class InertiaSharedData
{
    public function __construct(
        public readonly ArrayType $data,
    ) {
        //
    }
}
