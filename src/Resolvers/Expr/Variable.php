<?php

namespace Laravel\Ranger\Resolvers\Expr;

use Illuminate\Support\Collection;
use Laravel\Ranger\Resolvers\AbstractResolver;
use Laravel\Ranger\Types\ArrayType;
use Laravel\Ranger\Types\Contracts\Type as ResultContract;
use Laravel\Ranger\Types\Type as RangerType;
use PhpParser\Node;

class Variable extends AbstractResolver
{
    public function resolve(Node\Expr\Variable $node): ResultContract
    {
        $class = $this->parser->nodeFinder()->findFirst(
            $this->parsed,
            fn ($n) => $n instanceof Node\Stmt\Class_
        );

        if (! $class) {
            // TODO: Handle case where no class is found, fallback to a closure
            return RangerType::mixed();
        }

        if ($node->name === 'this') {
            return RangerType::string($class->namespacedName->name);
        }

        // TODO: Handle case where no method is found, fallback to a closure
        $method = $this->parser->nodeFinder()->findFirst(
            $this->parsed,
            fn ($n) => $n instanceof Node\Stmt\ClassMethod &&
                $n->getStartLine() <= $node->getStartLine() &&
                $n->getEndLine() >= $node->getEndLine()
        );

        if (! $method) {
            return RangerType::mixed();
        }

        $paramType = $this->reflector->methodParamType($class->namespacedName->name, $method->name->name, $node->name);

        if ($paramType) {
            return $paramType;
        }

        $ifStack = 0;
        $value = RangerType::mixed();
        $values = [];

        $this->parser->walk([$method], function (Node $n) use (&$ifStack, &$values, &$value, $node) {
            if ($n instanceof Node\Stmt\If_) {
                $ifStack++;
            }

            if (
                $n instanceof Node\Stmt\Expression &&
                $n->expr instanceof Node\Expr\Assign &&
                $n->expr->var instanceof Node\Expr\Variable &&
                $n->expr->var->name === $node->name &&
                $n->getStartLine() < $node->getStartLine()
            ) {
                if ($ifStack === 0) {
                    $docBlock = $n->getDocComment();

                    if ($docBlock && ($parsed = $this->docBlockParser->parseVar($docBlock))) {
                        $value = $parsed;
                    } else {
                        $value = $n->expr->expr;
                    }
                } else {
                    $values[] = $n->expr->expr;
                }
            }
        }, function (Node $n) use (&$ifStack) {
            if ($n instanceof Node\Stmt\If_) {
                $ifStack--;
            }
        });

        $value = $this->from($value);
        $values = array_map(fn ($v) => $this->from($v), $values);

        [$arrayValues, $nonArrayValues] = collect([$value, ...$values])->partition(fn ($v) => $v instanceof ArrayType);

        $newArrayValue = $this->handleArrayValues($arrayValues);

        if ($newArrayValue === null && $nonArrayValues->isEmpty()) {
            return RangerType::mixed();
        }

        if ($newArrayValue === null) {
            return RangerType::union(...$nonArrayValues);
        }

        return RangerType::union($newArrayValue, ...$nonArrayValues);
    }

    protected function handleArrayValues(Collection $arrayValues): ?ArrayType
    {
        if ($arrayValues->isEmpty()) {
            return null;
        }

        $keys = $arrayValues->map(fn (ArrayType $v) => $v->keys());

        $requiredKeys = array_intersect(...$keys->toArray());

        $newArrayValue = [];

        foreach ($arrayValues as $arrayValue) {
            foreach ($arrayValue->value as $key => $val) {
                $val->required(in_array($key, $requiredKeys));

                $newArrayValue[$key] ??= [];
                $newArrayValue[$key][] = $val;
            }
        }

        foreach ($newArrayValue as $key => $values) {
            if (count($values) === 1) {
                $newArrayValue[$key] = $values[0];
            } else {
                $newArrayValue[$key] = RangerType::union(...$values);
            }
        }

        return RangerType::array($newArrayValue);
    }
}
