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

    private static $instance_;

    public static function getInstance(): self
    {
        return self::$instance_ ?? (self::$instance_ = new self());
    }

    /// Remove all cached data
    public function clear(): void
    {
        $this->data_ = [];
    }
}
