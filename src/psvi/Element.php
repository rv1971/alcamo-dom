<?php

namespace alcamo\dom\psvi;

use alcamo\dom\decorated\{AbstractElementDecorator, Element as BaseElement};
use alcamo\dom\schema\component\TypeInterface;
use alcamo\exception\DataValidationFailed;

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

    /// @copybrief alcamo::dom::extended::Element::createValue()
    protected function createValue()
    {
        try {
            /** Convert based on the XML Schema type. */
            return $this->ownerDocument->getConverter()
                ->convert($this->textContent, $this, $this->getType());
        } catch (ExceptionInterface $e) {
            throw $e->addMessageContext(
                [
                    'inData' => $this->ownerDocument->saveXML(),
                    'atUri' => $this->ownerDocument->documentURI,
                    'atLine' => $this->getLineNo(),
                    'forKey' => $this->name
                ]
            );
        } catch (\Throwable $e) {
            throw DataValidationFailed::newFromPrevious(
                $e,
                [
                    'inData' => $this->ownerDocument->saveXML(),
                    'atUri' => $this->ownerDocument->documentURI,
                    'atLine' => $this->getLineNo(),
                    'forKey' => $this->name
                ]
            );
        }
    }
}
