<?php

namespace Laravel\Ranger\Support;

trait HasPaths
{
    /**
     * @var list<string>
     */
    protected array $appPaths = [];

    /**
     * @var list<string>
     */
    protected array $basePaths = [];

    /**
     * Set the base path(s) for the collectors.
     */
    public function setBasePaths(string ...$paths): static
    {
        $this->basePaths = $paths;

        return $this;
    }

    /**
     * Set the app path(s) for the collectors.
     */
    public function setAppPaths(string ...$paths): static
    {
        $this->appPaths = $paths;

        return $this;
    }
}
