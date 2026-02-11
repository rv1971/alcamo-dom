<?php

namespace alcamo\dom\extended;

use alcamo\dom\Element as BaseElement;

/**
 * @brief Element class for use in DOMDocument::registerNodeClass()
 *
 * @date Last reviewed 2025-11-05
 */
class Element extends BaseElement implements DomNodeInterface
{
    use HavingLangTrait;
    use HavingPositionTrait;
    use MagicAttrAccessTrait;

    private $value_;

    /// Call createValue() and cache the result
    public function getValue()
    {
        /** Call alcamo::dom::extended::RegisteredNodeTrait::register(). See
         *  alcamo::dom::extended::RegisteredNodeTrait for an explanation why
         *  this is necessary. */
        if (!isset($this->value_)) {
            $this->value_ = $this->createValue();
            $this->register();
        }

        return $this->value_;
    }

    /**
     * @brief Create a value for use in getValue()
     *
     * This implementation simply returns the text content. It is meant to
     * be overriden by more sophisticated methods in derived classes.
     */
    protected function createValue()
    {
        return $this->textContent;
    }
}
