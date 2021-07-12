<?php

namespace alcamo\dom\decorated;

use alcamo\dom\DocumentFactoryInterface;
use alcamo\dom\psvi\Document as BaseDocument;
use alcamo\dom\schema\TypeMap;

/**
 * @namespace alcamo::dom::decorator
 *
 * @brief DOM classes providing element-type-specific element decorator
 * objects
 */

/**
 * @brief DOM class for %Documents whose element objects have
 * element-type-specific decorators
 *
 * The DOM framework has no means to generate different subclasses of
 * DOMElement for different XML element types. This document class allows to
 * delegate element-type-specific functionality to element decorator objects.
 *
 * @date Last reviewed 2021-07-12
 */
class Document extends BaseDocument
{
    /// @copybrief alcamo::dom::Document::NODE_CLASSES
    public const NODE_CLASSES =
        [
            'DOMElement' => Element::class
        ]
        + parent::NODE_CLASSES;

    /**
     * @brief Map of XSD type XNames to decorator classes for elements
     *
     * To be overridden in derived classes.
     */
    public const ELEMENT_DECORATOR_MAP = [];

    /**
     * @brief Default decorator class to use if no entry is found in @ref
     * ELEMENT_DECORATOR_MAP
     */
    public const DEFAULT_DECORATOR_CLASS = null;

    private $elementDecoratorMap_; /// TypeMap

    /// @copybrief alcamo::dom::Document::getDocumentFactory()
    public function getDocumentFactory(): DocumentFactoryInterface
    {
        return new DocumentFactory();
    }

    /// Map of XSD element types to decorator classes
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
