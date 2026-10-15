<?php

namespace alcamo\dom;

use alcamo\collection\{
    ArrayDataTrait,
    CountableTrait,
    PreventWriteArrayAccessTrait,
    StringIndexedReadArrayAccessTrait
};

/**
 * @brief Trait for cache classes
 *
 * Provides a singleton with readonly ArrayAccess interface.
 */
trait CacheTrait
{
    use ArrayDataTrait;
    use CountableTrait;
    use PreventWriteArrayAccessTrait;
    use StringIndexedReadArrayAccessTrait;

    public static function getInstance(): self
    {
        static $instance;

        return $instance ?? ($instance = new self());
    }

    /**
     * @brief Remove all cached data
     *
     * Mainly for testing and debugging purposes.
     *
     * Classes using this trait may add initial values to the cache.
    */
    public function init(): void
    {
        $this->data_ = [];
    }

    private function __construct()
    {
        $this->init();
    }
}
