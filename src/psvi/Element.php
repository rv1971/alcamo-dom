<?php

namespace alcamo\dom\psvi;

use alcamo\dom\decorated\{AbstractElementDecorator, Element as BaseElement};
use alcamo\dom\schema\component\TypeInterface;

/**
 * @brief Element class for use in DOMDocument::registerNodeClass()
 *
 * @date Last reviewed 2025-11-25
 */
class Element extends BaseElement
{
    private $type_ = false;  ///< TypeInterface

    /** @copybrief alcamo::dom::decorated::element::DEFAULT_DECORATOR_CLASS */
    public const DEFAULT_DECORATOR_CLASS = HavingDocumentationDecorator::class;

    public function getType(): TypeInterface
    {
        if ($this->type_ === false) {
            $this->type_ = $this->ownerDocument->getSchema()
                ->lookupElementType($this);
        }

        return $this->type_;
    }

    /** This implementation calls the constructor of a class looked up in
     *  Document::getElementDecoratorMap() unless the parent already supplies
     *  a decorator. Derived classes may implement other mechanisms.
     */
    protected function createDecorator(): ?AbstractElementDecorator
    {
        $decorator = parent::createDecorator();

        /** If the parent class supplies a decorator which is not the default,
         *  return it. This ensures, for instance, that XSD elements always
         *  have the XSD-specific decorator. */
        if (
            isset($decorator)
                && get_class($decorator) != static::DEFAULT_DECORATOR_CLASS
        ) {
            return $decorator;
        }

        $class = $this->ownerDocument->getElementDecoratorMap()
            ->lookup($this->getType());

        if (isset($class)) {
            return new $class($this);
        }

        return $decorator;
    }
}
