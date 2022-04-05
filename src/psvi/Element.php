<?php

namespace alcamo\dom\psvi;

use alcamo\dom\decorated\{AbstractDecorator, Element as BaseElement};
use alcamo\dom\schema\component\TypeInterface;

/**
 * @brief %Element class for use in DOMDocument::registerNodeClass()
 *
 * Provides getType() to retrieve the XSD type of this element.
 */
class Element extends BaseElement
{
    private $type_ = false;  ///< TypeInterface

    public const DEFAULT_DECORATOR_CLASS = GetLabelDecorator::class;

    public function getType(): TypeInterface
    {
        if ($this->type_ === false) {
            $this->type_ = $this->ownerDocument->getSchema()
                ->lookupElementType($this);
        }

        return $this->type_;
    }

    /** The default implementation calls the constructor of a class looked up
     *  in Document::getElementDecoratorMap(). Derived classes may implement
     *  other mechanisms. */
    protected function createDecorator(): ?AbstractDecorator
    {
        $className = $this->ownerDocument->getElementDecoratorMap()
            ->lookup($this->getType());

        if (isset($className)) {
            return new $className($this);
        }

        return parent::createDecorator();
    }
}
