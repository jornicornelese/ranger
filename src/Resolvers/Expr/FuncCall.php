<?php

namespace Laravel\Ranger\Resolvers\Expr;

use Laravel\Ranger\Resolvers\AbstractResolver;
use Laravel\Ranger\Types\ArrayType;
use Laravel\Ranger\Types\Contracts\Type;
use Laravel\Ranger\Types\Contracts\Type as ResultContract;
use Laravel\Ranger\Types\Type as RangerType;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;

class FuncCall extends AbstractResolver
{
    public function resolve(Node\Expr\FuncCall $node): ResultContract
    {
        if ($node->name instanceof Variable) {
            return $this->from($node->name);
        }

        if ($node->name->toString() === 'array_merge') {
            $arrays = collect($node->args)->map($this->from(...));
            $finalArray = collect();

            foreach ($arrays as $array) {
                if ($array instanceof ArrayType) {
                    $finalArray = $finalArray->merge($array->value);
                } else {
                    dd('Unsupported array_merge argument type', $array);
                }

                // $finalArray['[key: string]'] = 'mixed';
            }

            if ($finalArray->keys()->every(fn ($key) => is_int($key))) {
                dd('is list', $finalArray);

                return RangerType::array([]);
            }

            return RangerType::array($finalArray);
        }

        $stanType = $this->getStanType($node);

        if ($stanType !== null) {
            return $stanType;
        }

        $result = $this->reflector->functionReturnType($node->name->toString(), $node);

        if ($result instanceof Type) {
            return $result;
        }

        if ($result === null || (is_array($result) && count($result) === 0)) {
            return RangerType::mixed();
        }

        if (is_array($result) && count($result) === 1) {
            return RangerType::from($result[0]);
        }

        return RangerType::from($result);
    }
}
