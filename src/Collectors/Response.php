<?php

namespace Laravel\Ranger\Collectors;

use Closure;
use Laravel\Ranger\Components\JsonResponse;
use Laravel\Ranger\Support\AnalyzesRoutes;
use Laravel\Surveyor\Analyzed\MethodResult;
use Laravel\Surveyor\Analyzer\Analyzer;
use Laravel\Surveyor\Types\ArrayType;
use Laravel\Surveyor\Types\ClassType;
use Laravel\Surveyor\Types\Contracts\MultiType;
use Laravel\Surveyor\Types\Entities\InertiaRender;

class Response
{
    use AnalyzesRoutes;

    public function __construct(protected Analyzer $analyzer)
    {
        //
    }

    /**
     * @return list<InertiaResponse|JsonResponse>
     */
    public function parseResponse(array $action): array
    {
        $result = $this->analyzeRoute($action);

        if (! $result) {
            return [];
        }

        return array_merge(
            $this->getInertiaResponse($result),
            $this->getJsonResponse($result),
        );
    }

    protected function getInertiaResponse(MethodResult $result): array
    {
        /** @var InertiaRender[] $responses */
        $responses = $this->filterReturnTypesFor(
            $result,
            fn ($type) => $type instanceof InertiaRender,
        );

        foreach ($responses as $response) {
            $data = $response->data;

            if ($data instanceof ClassType) {
                $data = $this->resolveClassType($data) ?? new ArrayType([]);
            }

            InertiaComponents::addComponent($response->view, $data);
        }

        return array_map(fn ($response) => $response->view, $responses);
    }

    protected function getJsonResponse(MethodResult $result): array
    {
        /** @var ArrayType[] $responses */
        $responses = $this->filterReturnTypesFor(
            $result,
            fn ($type) => $type instanceof ArrayType,
        );

        /** @var ClassType[] $classResponses */
        $classResponses = $this->filterReturnTypesFor(
            $result,
            fn ($type) => $type instanceof ClassType && ! $type instanceof InertiaRender,
        );

        $resolvedResponses = array_filter(
            array_map(fn ($type) => $this->resolveClassType($type), $classResponses),
        );

        $allResponses = array_merge($responses, $resolvedResponses);

        return array_map(fn ($response) => new JsonResponse($response->value), $allResponses);
    }

    protected function resolveClassType(ClassType $classType): ?ArrayType
    {
        $className = $classType->resolved();

        if (! class_exists($className)) {
            return null;
        }

        $classResult = $this->analyzer->analyzeClass($className)->result();

        if ($classResult->isArrayable() && $classResult->hasMethod('toArray')) {
            $returnType = $classResult->asArray()?->returnType();
        } elseif ($classResult->isJsonSerializable() && $classResult->hasMethod('jsonSerialize')) {
            $returnType = $classResult->asJson()?->returnType();
        } elseif ($classResult->hasMethod('toArray')) {
            $returnType = $classResult->getMethod('toArray')->returnType();
        } else {
            return null;
        }

        return $returnType instanceof ArrayType ? $returnType : null;
    }

    protected function filterReturnTypesFor(MethodResult $result, Closure $filter): array
    {
        $returnType = $result->returnType();
        $returnTypes = ($returnType instanceof MultiType) ? $returnType->types : [$returnType];

        return array_values(array_filter($returnTypes, $filter));
    }
}
