<?php

namespace Laravel\Ranger\Resolvers\Expr;

use Laravel\Ranger\Known\Known;
use Laravel\Ranger\Resolvers\AbstractResolver;
use Laravel\Ranger\Types\ArrayShapeType;
use Laravel\Ranger\Types\ClassType;
use Laravel\Ranger\Types\Contracts\Type as ResultContract;
use Laravel\Ranger\Types\Type as RangerType;
use PhpParser\Node;

class StaticCall extends AbstractResolver
{
    public function resolve(Node\Expr\StaticCall $node): ResultContract
    {
        if (
            method_exists($node->class, 'toString')
            && ($known = Known::resolve($node->class->toString(), $node->name->name, ...$node->getArgs())) !== false
        ) {
            return RangerType::from($known);
        }

        $stanType = $this->getStanType($node);

        if ($stanType instanceof ClassType) {
            $return = match ($stanType->value) {
                'Inertia\\LazyProp' => RangerType::from($this->from($node->getArgs()[0]))->optional(),
                'Inertia\\AlwaysProp' => RangerType::from($this->from($node->getArgs()[0])),
                default => null,
            };

            if ($return) {
                return $return;
            }
        }

        if ($stanType !== null) {
            return $stanType;
        }

        $varType = $this->from($node->class);

        if ($varType instanceof ClassType || (is_string($varType) && class_exists($varType))) {
            $resolvedClass = $varType;
            $varType = $varType;

            if ($varType instanceof ClassType) {
                $resolvedClass = $varType->resolved();
                $varType = $varType->value;
            }

            if ($resolvedClass !== $varType) {
                $classCandidates = [$this->reflector->reflectClass($resolvedClass)];
                $docBlock = $classCandidates[0]->getDocComment();

                if ($docBlock) {
                    $mixins = $this->docBlockParser->parseMixins($docBlock, $classCandidates);

                    foreach ($mixins as $mixin) {
                        if (class_exists($mixin->value)) {
                            $classCandidates[] = $this->reflector->reflectClass($mixin->value);
                        }
                    }
                }

                foreach ($classCandidates as $reflection) {
                    $parsed = $this->parser->parse($reflection);

                    $methodNode = $this->parser->nodeFinder()->findFirst(
                        $parsed,
                        static fn (Node $n) => $n instanceof Node\Stmt\ClassMethod && $n->name->name === $node->name->name,
                    );

                    if ($methodNode !== null) {
                        return $this->from($methodNode);
                    }
                }
            }

            $returnType = $this->reflector->methodReturnType($resolvedClass, $node->name->name, $node);

            // TODO: Ew
            // Try to get something more specific if we can
            if ($returnType instanceof ArrayShapeType) {
                $reflection = $this->reflector->reflectMethod($resolvedClass, $node->name->name);
                $foundNode = null;

                $returns = collect($this->parser->nodeFinder()->find(
                    $this->parser->parse($reflection),
                    function ($n) use ($reflection, &$foundNode) {
                        if (
                            $n->getStartLine() < $reflection->getStartLine() ||
                            $n->getEndLine() > $reflection->getEndLine()
                        ) {
                            return false;
                        }

                        $foundNode ??= $n;

                        if (! ($n instanceof Node\Stmt\Return_)) {
                            return false;
                        }

                        $parent = $n->getAttribute('parent');

                        while ($parent && $parent !== $foundNode) {
                            if ($parent instanceof Node\Expr\Closure) {
                                return false;
                            }

                            $parent = $parent->getAttribute('parent');
                        }

                        return $parent === $foundNode;
                    }
                ));

                $result = collect($returns)->map(fn ($n) => $this->from($n->expr))->filter();

                if ($result->isNotEmpty()) {
                    return RangerType::union(...$result->all());
                }
            }

            if ($returnType) {
                return $returnType;
            }
        }

        return RangerType::mixed();
    }
}
