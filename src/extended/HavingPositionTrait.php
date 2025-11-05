<?php

namespace alcamo\dom\extended;

/**
 * @brief Provides position of a node within its parent
 *
 * When calling getPosition() a second time, the result is taken from the cache.
 *
 * @warning The cached result is never updated, not even when children are
 * inserted before this node.
 *
 * @date Last reviewed 2025-11-05
 */
trait HavingPositionTrait
{
    use RegisteredNodeTrait;

    private $position_ = false; ///< int

    /**
     * @brief Return position within parent
     *
     * The first child element has position 1. Only element nodes are counted.
     */
    public function getPosition(): int
    {
        if ($this->position_ === false) {
            // Ensure conservation of the derived object.
            $this->register();

            $this->position_ =
                $this->evaluate('count(preceding-sibling::*)') + 1;
        }

        return $this->position_;
    }
}
