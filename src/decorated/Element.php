<?php

namespace alcamo\dom\decorated;

use alcamo\exception\MethodNotFound;
use alcamo\dom\extended\{Element as BaseElement, GetLangTrait};
use alcamo\dom\xsd\Decorator as XsdDecorator;

/**
 * @brief Element implementing the decorator pattern
 *
 * The DOM framework has no means to generate different subclasses of
 * DOMElement for different XML elements. This element class allows to
 * delegate element-specific functionality to decorator objects.
 */
class Element extends BaseElement
{
    use GetLangTrait;

    /// Map of element NSs to maps of element local names to decorator classes
    public const DECORATOR_MAP = [
        Document::XSD_NS => [
            '*' => XsdDecorator::class
        ]
    ];

    /**
     * @brief Default decorator class to use if no entry is found in @ref
     * ELEMENT_DECORATOR_MAP
     *
     * May also be overridden with `null` in derived classes.
     */
    public const DEFAULT_DECORATOR_CLASS = GetLabelAndCommentDecorator::class;

    private $decorator_ = false; ///< ?AbstractDecorator

    /// The decorator object
    public function getDecorator(): ?AbstractDecorator
    {
        if ($this->decorator_ === false) {
            // Ensure conservation of the derived object.
            $this->register();

            $this->decorator_ = $this->createDecorator();
        }

        return $this->decorator_;
    }

    /// Delegate method calls to the decorator object, if any
    public function __call(string $name, array $params)
    {
        /* Call a method in the decorator only if it exists. Otherwise the
         * decorator would look for it in the Element class, leading to an
         * infinite recursion that would end up in a stack overflow. */
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

    /** The default implementation calls the constructor of a class looked up
     *  in @ref DECORATOR_MAP. Derived classes may implement other
     *  mechanisms. */
    protected function createDecorator(): ?AbstractDecorator
    {
        if (isset(static::DECORATOR_MAP[$this->namespaceURI])) {
            $className =
                static::DECORATOR_MAP[$this->namespaceURI][$this->localName]
                ?? static::DECORATOR_MAP[$this->namespaceURI]['*']
                ?? static::DEFAULT_DECORATOR_CLASS;
        } else {
            $className = static::DEFAULT_DECORATOR_CLASS;
        }

        return isset($className) ? new $className($this) : null;
    }
}
