<?php

namespace Laravel\Ranger\Collectors;

use Illuminate\Support\Collection;

abstract class Collector
{
    protected Collection $cached;

    /**
     * @param  callable[]  $callbacks
     */
    public function run(array $callbacks): void
    {
        foreach ($callbacks as $callback) {
            $this->getCollection()->each(fn ($item) => $callback($item));
        }
    }

    /**
     * @param  callable[]  $callbacks
     */
    public function runOnCollection(array $callbacks): void
    {
        collect($callbacks)->each(fn ($callback) => $callback($this->getCollection()));
    }

    public function getCollection(): Collection
    {
        return $this->cached ??= $this->collect();
    }

    abstract public function collect(): Collection;
}
