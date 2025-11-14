<?php

namespace Laravel\Ranger\Components;

use Laravel\Ranger\Concerns\HasFilePath;

class Enum
{
    use HasFilePath;

    public function __construct(
        public readonly string $name,
        public readonly array $cases
    ) {
        //
    }
}
