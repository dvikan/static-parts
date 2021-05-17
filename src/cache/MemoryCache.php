<?php
declare(strict_types=1);

namespace dvikan\SimpleParts;

final class MemoryCache implements Cache
{
    private $cache = [];
    private $clock;

    public function __construct(Clock $clock = null)
    {
        $this->cache = [];
        $this->clock = $clock ?? new SystemClock();
    }

    public function set(string $key, $value = true, int $ttl = 0): void
    {
        $this->cache[$key] = [
            'value'             => $value,
            'ttl'               => $ttl,
            'created_at'        => $this->clock->now()->getTimestamp(),
        ];
    }

    public function get(string $key, $default = null)
    {
        if (! isset($this->cache[$key])) {
            return $default;
        }

        if ($this->cache[$key]['ttl'] === 0) {
            return $this->cache[$key]['value'];
        }

        if ($this->cache[$key]['created_at'] + $this->cache[$key]['ttl'] < $this->clock->now()->getTimestamp()) {
            unset($this->cache[$key]);
            return $default;
        }

        return $this->cache[$key]['value'];
    }

    public function delete(string $key): void
    {
        unset($this->cache[$key]);
    }

    public function clear(): void
    {
        $this->cache = [];
    }
}