<?php

namespace Avdeb\Lib\Store;

/** Minimal key/value store for the catalog client (Craft cache in production, an array in tests). */
interface StoreInterface
{
    public function get(string $key): mixed;

    public function set(string $key, mixed $value): void;

    public function delete(string $key): void;

    /** Drop every entry ("Refresh products now"). */
    public function clear(): void;
}
