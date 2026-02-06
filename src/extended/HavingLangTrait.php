<?php

namespace alcamo\dom\extended;

use alcamo\rdfa\Lang;

/**
 * @brief Provide language of a node
 *
 * When calling getLang() a second time, the result is taken from the cache.
 *
 * @warning The cached result is never updated, not even when the language of
 * the node or one of its ancestors is changed.
 *
 * @date Last reviewed 2025-11-05
 */
trait HavingLangTrait
{
    use RegisteredNodeTrait;

    private $lang_ = false; ///< ?Lang

    /// Return xml:lang of element or closest ancestor
    public function getLang(): ?Lang
    {
        /** Unlike alcamo::dom::Element, here the result is cached. */
        if ($this->lang_ === false) {
            /* Ensure conservation of the derived object. */
            $this->register();

            $this->lang_ = parent::getLang();
        }

        return $this->lang_;
    }
}
