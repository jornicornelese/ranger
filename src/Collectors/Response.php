<?php

namespace Laravel\Ranger\Collectors;

use Closure;
use Laravel\Ranger\Components\JsonResponse;
use Laravel\Ranger\Debug;
use Laravel\Surveyor\Analyzed\MethodResult;
use Laravel\Surveyor\Analyzer\Analyzer;
use Laravel\Surveyor\Types\ArrayType;
use Laravel\Surveyor\Types\Contracts\MultiType;
use Laravel\Surveyor\Types\Entities\InertiaRender;
use ReflectionFunction;

class Response
{
    public function __construct(
        protected Analyzer $analyzer,
    ) {
        //
    }

    public function parseResponse(array $routeUses): array
    {
        if ($routeUses['uses'] instanceof Closure) {
            $reflection = new ReflectionFunction($routeUses['uses']);
        } else {
            [$controller, $method] = explode('@', $routeUses['uses']);
            $analyzed = $this->analyzer->analyzeClass($controller)->result();

            if (! $analyzed->hasMethod($method)) {
                Debug::log("Method {$method} not found in class {$controller}");

                return [];
            }

            $reflection = $analyzed->getMethod($method);
        }

        if (! $reflection instanceof MethodResult) {
            // TODO: Deal with closures
            info('Non-method reflection in route uses!');

            return [];
        }

        return array_merge(
            $this->getInertiaResponse($reflection),
            $this->getJsonResponse($reflection),
        );
    }

    protected function getInertiaResponse(MethodResult $result): array
    {
        /** @var InertiaRender[] $responses */
        $responses = $this->filterReturnTypesFor($result, fn ($type) => $type instanceof InertiaRender);

        foreach ($responses as $response) {
            InertiaComponents::addComponent($response->view, $response->data);
        }

        return array_map(fn ($response) => $response->view, $responses);
    }

    protected function getJsonResponse(MethodResult $result): array
    {
        /** @var ArrayType[] $responses */
        $responses = $this->filterReturnTypesFor($result, fn ($type) => $type instanceof ArrayType);

        return array_map(fn ($response) => new JsonResponse($response->value), $responses);
    }

    protected function filterReturnTypesFor(MethodResult $result, Closure $filter): array
    {
        $returnType = $result->returnType();
        $returnTypes = ($returnType instanceof MultiType) ? $returnType->types : [$returnType];

        return array_values(array_filter($returnTypes, $filter));
    }
}
