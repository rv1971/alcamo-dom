<?php

namespace alcamo\dom\psvi;

use alcamo\dom\extended\Attr as BaseAttr;
use alcamo\dom\schema\component\{
    ComplexType,
    EnumerationTypeInterface,
    ListType,
    SimpleTypeInterface
};
use alcamo\exception\{DataValidationFailed, ExceptionInterface};

/**
 * @brief Attribute class for use in DOMDocument::registerNodeClass()
 *
 * Provides getType() to retrieve the XSD type of this attribute and uses this
 * in createValue(), so that alcamo::dom::extended::Attr::getValue() returns
 * an appropriately converted value.
 *
 * @date Last reviewed 2025-11-25
 */
class Attr extends BaseAttr
{
    private $type_;  ///< SimpleTypeInterface

    /// Get the attribute type as precisely as possible
    public function getType(): SimpleTypeInterface
    {
        if (!isset($this->type_)) {
            /** - If the attribute has a namespace, look for a global
             *  attribute declaration, otherwise for an attribute declaration
             *  in the element's type. If there is one, takt its type. */

            $attrDecl = isset($this->namespaceURI)
                ? $this->ownerDocument->getSchema()
                ->getGlobalAttr((string)$this->getXName())
                : ($this->parentNode->getType()
                   ->getAttrs()[(string)$this->getXName()] ?? null);

            /** - Otherwise, the attribute type is `xsd:anySimpleType`. This
             *  is not necessarily an error since the element could use
             *  `xsd:anyAttribute`. */

            $this->type_ = isset($attrDecl)
                ? $attrDecl->getType()
                : $this->ownerDocument->getSchema()->getAnySimpleType();
        }

        return $this->type_;
    }

    /// @copybrief alcamo::dom::extended::Attr::createValue()
    protected function createValue()
    {
        /** For attributes without namespace on XSD elements, fall back to
         *  alcamo::dom::extended::Attr::createValue() immediately. */
        if (
            !isset($this->namespaceURI)
            && $this->parentNode->namespaceURI == self::XSD_NS
        ) {
            return parent::createValue();
        }

        /** Otherwise, if alcamo::dom::extended::Attr::createValue()
         *  converts the value, return its result. */
        $value = parent::createValue();

        if ($value !== $this->value) {
            return $value;
        }

        try {
            /** Otherwise convert based on the XML Schema type. */
            return $this->ownerDocument->getConverter()
                ->convert($value, $this, $this->getType());
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
