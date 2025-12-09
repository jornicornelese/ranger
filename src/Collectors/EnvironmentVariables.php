<?php

namespace Laravel\Ranger\Collectors;

use Illuminate\Support\Collection;
use Laravel\Ranger\Components\EnvironmentVariable;

class EnvironmentVariables extends Collector
{
    /**
     * @return Collection<EnvironmentVariable>
     */
    public function collect(): Collection
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            return collect();
        }

        $envFile = file_get_contents($envPath);

        return collect($_ENV)
            ->filter(fn ($_, $key) => preg_match('/^'.$key.'=/m  ', $envFile) === 1)
            ->map(fn ($_, $key) => $this->toComponent($key))
            ->values();
    }

    protected function toComponent(string $envKey): EnvironmentVariable
    {
        return new EnvironmentVariable($envKey, $this->resolveValue(env($envKey)));
    }

    protected function resolveValue(mixed $value): mixed
    {
        if (is_float($value)) {
            return (float) $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $value;
    }
}
