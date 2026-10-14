<?php

namespace App\Tenancy;

use App\Models\Shop;
use LogicException;

/**
 * The shop the current request (or job) acts on behalf of.
 *
 * Bound as a scoped singleton, so it is reset between requests and queued jobs.
 */
class CurrentShop
{
    private ?Shop $shop = null;

    public function set(Shop $shop): void
    {
        $this->shop = $shop;
    }

    public function clear(): void
    {
        $this->shop = null;
    }

    public function get(): ?Shop
    {
        return $this->shop;
    }

    public function id(): ?int
    {
        return $this->shop?->id;
    }

    public function getOrFail(): Shop
    {
        return $this->shop ?? throw new LogicException('No current shop has been resolved.');
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public function runAs(Shop $shop, callable $callback): mixed
    {
        $previous = $this->shop;
        $this->shop = $shop;

        try {
            return $callback();
        } finally {
            $this->shop = $previous;
        }
    }
}
