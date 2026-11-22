<?php

namespace Avdeb\Lib\Store;

/** In-memory store (tests, or a request-local fallback when the cache can't be written). */
final class ArrayStore implements StoreInterface
{
    /** @var array<string, string> serialized values, so callers never share references */
    public array $data = [];

    public function get(string $key): mixed
    {
        return isset($this->data[$key]) ? unserialize($this->data[$key], ['allowed_classes' => false]) : null;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = serialize($value);
    }

    public function delete(string $key): void
    {
        unset($this->data[$key]);
    }

    public function clear(): void
    {
        $this->data = [];
    }
}
