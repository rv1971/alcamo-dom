<?php

namespace alcamo\dom;

use alcamo\collection\{
    ArrayDataTrait,
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
    use PreventWriteArrayAccessTrait;
    use StringIndexedReadArrayAccessTrait;

    private static $instance_;

    public static function getInstance(): self
    {
        return self::$instance_ ?? (self::$instance_ = new self());
    }
}
