<?php

namespace alcamo\dom\extended;

/**
 * @brief Provides position of a node within its parent
 *
 * When calling getLang() a second time, the result is taken from the cache.
 *
 * @warning The cached result is never updated, not even when the language of
 * the node or one of its ancestors is changed.
 */
trait GetPositionTrait
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
