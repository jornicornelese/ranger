<?php

namespace Laravel\Ranger\Concerns;

trait HasFilePath
{
    protected string $filePath;

    public function setFilePath(string $filePath): static
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function filePath(): string
    {
        return $this->filePath;
    }
}
