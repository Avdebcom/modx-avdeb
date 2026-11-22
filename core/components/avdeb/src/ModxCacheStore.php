<?php

namespace Avdeb;

use Avdeb\Lib\Store\StoreInterface;
use MODX\Revolution\modX;
use xPDO\xPDO;

/**
 * Key/value store for the catalog client in its own MODX cache partition (core/cache/avdeb/, or
 * whatever cache handler the site uses). Entries live for a week plus an hour (the "last good
 * copy" window); freshness is decided by the client. Clearing the site cache empties it.
 */
final class ModxCacheStore implements StoreInterface
{
    public const PARTITION = 'avdeb';
    private const TTL = 7 * 24 * 3600 + 3600;

    public function __construct(private readonly modX $modx)
    {
    }

    private static function key(string $key): string
    {
        // File cache keys become paths: keep them to safe characters.
        return preg_replace('/[^A-Za-z0-9_.-]/', '_', $key) ?? $key;
    }

    private function options(): array
    {
        return [xPDO::OPT_CACHE_KEY => self::PARTITION];
    }

    public function get(string $key): mixed
    {
        $value = $this->modx->cacheManager->get(self::key($key), $this->options());

        return $value === false ? null : $value;
    }

    public function set(string $key, mixed $value): void
    {
        $this->modx->cacheManager->set(self::key($key), $value, self::TTL, $this->options());
    }

    public function delete(string $key): void
    {
        $this->modx->cacheManager->delete(self::key($key), $this->options());
    }

    public function clear(): void
    {
        $this->modx->cacheManager->refresh([self::PARTITION => []]);
    }
}
