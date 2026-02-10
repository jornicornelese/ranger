<?php

namespace Laravel\Ranger;

use Laravel\Ranger\Collectors\Collector;
use Laravel\Ranger\Support\HasPaths;

class Ranger
{
    use HasPaths;

    /**
     * @var array<class-string<Collector>, callable[]>
     */
    protected array $callbacks = [];

    /**
     * @var array<class-string<Collector>, callable[]>
     */
    protected array $collectionCallbacks = [];

    /**
     * Register a callback to be called when a route is found.
     *
     * @param  callable(\Laravel\Ranger\Components\Route): void  $callback
     */
    public function onRoute(callable $callback): void
    {
        $this->addCallback(Collectors\Routes::class, $callback);
    }

    /**
     * Register a callback to be called when the entire collection of routes is found.
     *
     * @param  callable(\Illuminate\Support\Collection<\Laravel\Ranger\Components\Route>): void  $callback
     */
    public function onRoutes(callable $callback): void
    {
        $this->addCollectionCallback(Collectors\Routes::class, $callback);
    }

    /**
     * Register a callback to be called when a model is found.
     *
     * @param  callable(\Laravel\Ranger\Components\Model): void  $callback
     */
    public function onModel(callable $callback): void
    {
        $this->addCallback(Collectors\Models::class, $callback);
    }

    /**
     * Register a callback to be called when the entire collection of models is found.
     *
     * @param  callable(\Illuminate\Support\Collection<\Laravel\Ranger\Components\Model>): void  $callback
     */
    public function onModels(callable $callback): void
    {
        $this->addCollectionCallback(Collectors\Models::class, $callback);
    }

    /**
     * Register a callback to be called when an enum is found.
     *
     * @param  callable(\Laravel\Ranger\Components\Enum): void  $callback
     */
    public function onEnum(callable $callback): void
    {
        $this->addCallback(Collectors\Enums::class, $callback);
    }

    /**
     * Register a callback to be called when the entire collection of enums is found.
     *
     * @param  callable(\Illuminate\Support\Collection<\Laravel\Ranger\Components\Enum>): void  $callback
     */
    public function onEnums(callable $callback): void
    {
        $this->addCollectionCallback(Collectors\Enums::class, $callback);
    }

    /**
     * Register a callback to be called when a resource is found.
     *
     * @param  callable(\Laravel\Ranger\Components\Resource): void  $callback
     */
    public function onResource(callable $callback): void
    {
        $this->addCallback(Collectors\Resources::class, $callback);
    }

    /**
     * Register a callback to be called when the entire collection of resources is found.
     *
     * @param  callable(\Illuminate\Support\Collection<\Laravel\Ranger\Components\Resource>): void  $callback
     */
    public function onResources(callable $callback): void
    {
        $this->addCollectionCallback(Collectors\Resources::class, $callback);
    }

    /**
     * Register a callback to be called when a broadcast event is found.
     *
     * @param  callable(\Laravel\Ranger\Components\BroadcastEvent): void  $callback
     */
    public function onBroadcastEvent(callable $callback): void
    {
        $this->addCallback(Collectors\BroadcastEvents::class, $callback);
    }

    /**
     * Register a callback to be called when inertia shared data is found.
     *
     * @param  callable(\Laravel\Ranger\Components\InertiaSharedData): void  $callback
     */
    public function onInertiaSharedData(callable $callback): void
    {
        $this->addCallback(Collectors\InertiaSharedData::class, $callback);
    }

    /**
     * Register a callback to be called when the entire collection of broadcast events is found.
     *
     * @param  callable(\Illuminate\Support\Collection<\Laravel\Ranger\Components\BroadcastEvent>): void  $callback
     */
    public function onBroadcastEvents(callable $callback): void
    {
        $this->addCollectionCallback(Collectors\BroadcastEvents::class, $callback);
    }

    /**
     * Register a callback to be called when a broadcast channel is found.
     *
     * @param  callable(\Laravel\Ranger\Components\BroadcastChannel): void  $callback
     */
    public function onBroadcastChannel(callable $callback): void
    {
        $this->addCallback(Collectors\BroadcastChannels::class, $callback);
    }

    /**
     * Register a callback to be called when the entire collection of broadcast channels is found.
     *
     * @param  callable(\Illuminate\Support\Collection<\Laravel\Ranger\Components\BroadcastChannel>): void  $callback
     */
    public function onBroadcastChannels(callable $callback): void
    {
        $this->addCollectionCallback(Collectors\BroadcastChannels::class, $callback);
    }

    /**
     * Register a callback to be called when an environment variable is found.
     *
     * @param  callable(\Laravel\Ranger\Components\EnvironmentVariable): void  $callback
     */
    public function onEnvironmentVariable(callable $callback): void
    {
        $this->addCallback(Collectors\EnvironmentVariables::class, $callback);
    }

    /**
     * Register a callback to be called when the entire collection of environment variables is found.
     *
     * @param  callable(\Illuminate\Support\Collection<\Laravel\Ranger\Components\EnvironmentVariable>): void  $callback
     */
    public function onEnvironmentVariables(callable $callback): void
    {
        $this->addCollectionCallback(Collectors\EnvironmentVariables::class, $callback);
    }

    /**
     * Walk the collectors and run the registered callbacks.
     */
    public function walk(): void
    {
        foreach ($this->callbacks as $collector => $callbacks) {
            app($collector)
                ->setBasePaths(...$this->basePaths)
                ->setAppPaths(...$this->appPaths)
                ->run($callbacks);
        }

        foreach ($this->collectionCallbacks as $collector => $callbacks) {
            app($collector)
                ->setBasePaths(...$this->basePaths)
                ->setAppPaths(...$this->appPaths)
                ->runOnCollection($callbacks);
        }
    }

    /**
     * @param  class-string<Collector>  $type
     */
    protected function addCallback(string $type, callable $callback): void
    {
        $this->callbacks[$type] ??= [];
        $this->callbacks[$type][] = $callback;
    }

    /**
     * @param  class-string<Collector>  $type
     */
    protected function addCollectionCallback(string $type, callable $callback): void
    {
        $this->collectionCallbacks[$type] ??= [];
        $this->collectionCallbacks[$type][] = $callback;
    }
}
