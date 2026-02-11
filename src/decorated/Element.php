<?php

namespace alcamo\dom\decorated;

use alcamo\exception\MethodNotFound;
use alcamo\dom\extended\Element as BaseElement;
use alcamo\dom\xh\{
    LinkDecorator as XhLinkDecorator,
    MetaDecorator as XhMetaDecorator
        };
use alcamo\dom\xsd\{Decorator as XsdDecorator, Enumerator};

/**
 * @brief Element implementing the decorator pattern
 *
 * The DOM framework has no means to generate different subclasses of
 * DOMElement for different XML elements. This element class allows to
 * delegate element-specific functionality to decorator objects.
 *
 * @date Last reviewed 2025-11-05
 */
class Element extends BaseElement
{
    /**
     * @brief Map of element namespaces to maps of element local names to
     * decorator classes
     *
     * The element local name `*` matches all elements in that namespace that
     * are not explicitly listed.
     */
    public const DECORATOR_MAP = [
        self::XH_NS => [
            'link' => XhLinkDecorator::class,
            'meta' => XhMetaDecorator::class
        ],
        self::XSD_NS => [
            'enumeration' => Enumerator::class,
            '*' => XsdDecorator::class
        ]
    ];

    /**
     * @brief Default decorator class to use if no entry is found in @ref
     * ELEMENT_DECORATOR_MAP
     *
     * May also be overridden with `null` in derived classes.
     */
    public const DEFAULT_DECORATOR_CLASS = HavingDocumentationDecorator::class;

    private $decorator_ = false; ///< ?AbstractElementDecorator

    /// Create the decorator or get it from the cache
    public function getDecorator(): ?AbstractElementDecorator
    {
        if ($this->decorator_ === false) {
            // Ensure conservation of the derived object.
            $this->register();

            $this->decorator_ = $this->createDecorator();
        }

        return $this->decorator_;
    }

    /**
     * @brief Allow for element-specific implementations in the decorator
     *
     * Without this, it would not be possible to override an existing
     * __toString() implementation in a parent class.
     */
    public function __toString(): string
    {
        return $this->getDecorator();
    }

    /// Delegate method calls to the decorator object, if any
    public function __call(string $name, array $params)
    {
        /* Call a method in the decorator only if it exists. Otherwise the
         * decorator would look for it in the Element class, leading to an
         * infinite recursion. */
        if (method_exists($this->getDecorator(), $name)) {
            return call_user_func_array(
                [ $this->decorator_, $name ],
                $params
            );
        } else {
            /** @throw alcamo::exception::MethodNotFound if the method does
             *  not exist in the decorator object. */
            throw (new MethodNotFound())->setMessageContext(
                [
                    'object' => $this->decorator_,
                    'method' => $name
                ]
            );
        }
    }

    /** This implementation calls the constructor of a class looked up in @ref
     *  DECORATOR_MAP. Derived classes may implement other mechanisms. */
    protected function createDecorator(): ?AbstractElementDecorator
    {
        $decoratorMap = static::DECORATOR_MAP[$this->namespaceURI] ?? null;

        $className = isset($decoratorMap)
            ? ($decoratorMap[$this->localName]
               ?? $decoratorMap['*']
               ?? static::DEFAULT_DECORATOR_CLASS)
            : static::DEFAULT_DECORATOR_CLASS;

        return isset($className) ? new $className($this) : null;
    }
}
