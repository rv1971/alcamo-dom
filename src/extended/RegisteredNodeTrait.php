<?php

namespace alcamo\dom\extended;

trait RegisteredNodeTrait
{
    private $hash_;  ///< String.

    public function register()
    {
        if (!isset($this->hash_)) {
            $this->ownerDocument->register(
                $this,
                ($this->hash_ = spl_object_hash($this))
            );
        }

        return $this->hash_;
    }
}
