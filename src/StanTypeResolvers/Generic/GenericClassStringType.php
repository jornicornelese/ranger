<?php

namespace Laravel\Ranger\StanTypeResolvers\Generic;

use Laravel\Ranger\StanTypeResolvers\AbstractResolver;
use Laravel\Ranger\Types\Contracts\Type as TypeContract;
use Laravel\Ranger\Types\Type as TypesType;
use PHPStan\Type;

class GenericClassStringType extends AbstractResolver
{
    public function resolve(Type\Generic\GenericClassStringType $node): TypeContract
    {
        // TODO: Deal with this if we keep stan around
        return TypesType::string($node->getGenericType()->getClassName());
    }
}
