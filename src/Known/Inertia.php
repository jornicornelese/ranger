<?php

namespace Laravel\Ranger\Known;

use Laravel\Ranger\Types\Type;
use Laravel\Ranger\Util\TypeResolver;

class Inertia
{
    public static function optional(...$args)
    {
        return Type::from(app(TypeResolver::class)->from($args[0]))->optional();
    }

    public static function lazy(...$args)
    {
        return Type::from(app(TypeResolver::class)->from($args[0]))->optional();
    }

    public static function always(...$args)
    {
        return Type::from(app(TypeResolver::class)->from($args[0]));
    }
}
