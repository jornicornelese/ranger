<?php

namespace Laravel\Ranger\Components;

class JsonResponse
{
    public function __construct(
        public readonly array $data,
        public readonly ?string $wrap = null,
        public readonly bool $isCollection = false,
        public readonly ?string $resourceClass = null,
        public readonly bool $isPaginated = false,
    ) {
        //
    }
}
