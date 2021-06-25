<?php

namespace alcamo\dom\decorated;

use alcamo\dom\DocumentFactoryInterface;
use alcamo\dom\psvi\Document as BaseDocument;
use alcamo\dom\schema\TypeMap;

class Document extends BaseDocument
{
    public const NODE_CLASS =
        [
            'DOMElement' => Element::class
        ]
        + parent::NODE_CLASS;

    public const ELEMENT_DECORATOR_MAP = [];

    public const DEFAULT_DECORATOR_CLASS = null;

    /// Map of element types to decorator classes
    private $elementDecoratorMap_;

    public function getDocumentFactory(): DocumentFactoryInterface
    {
        return new DocumentFactory();
    }

    public function getElementDecoratorMap(): TypeMap
    {
        if (!isset($this->elementDecoratorMap_)) {
            $this->elementDecoratorMap_ = TypeMap::newFromSchemaAndXNameMap(
                $this->getSchema(),
                static::ELEMENT_DECORATOR_MAP,
                static::DEFAULT_DECORATOR_CLASS
            );
        }

        return $this->elementDecoratorMap_;
    }

    /** The default implementation calls the constructor of a class looked up
     *  in a TypeMap. Derived classes may implement other mechanisms. */
    public function createDecorator(Element $element): ?AbstractDecorator
    {
        $className =
            $this->getElementDecoratorMap()->lookup($element->getType());

        return $className ? new $className($element) : null;
    }
}
