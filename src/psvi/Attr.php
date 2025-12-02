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
            do {
                $elementType = $this->parentNode->getType();

                /** - If the element type is unknown, the attribute type is
                 *  `xsd:anySimpleType`. */
                if (!($elementType instanceof ComplexType)) {
                    $this->type_ =
                        $this->ownerDocument->getSchema()->getAnySimpleType();
                    break;
                }

                $attr = $elementType->getAttrs()[(string)$this->getXName()]
                    ?? null;

                /** - Otherwise, if the element type does not define the
                 *  current attribute, the attribute type is
                 *  `xsd:anySimpleType`. This is not necessarily an error
                 *  since the element could use `xsd:anyAttribute`. */
                if (!isset($attr)) {
                    $this->type_ =
                        $this->ownerDocument->getSchema()->getAnySimpleType();
                    break;
                }

                /** Otherwise, the type is obtained from the attribute
                 *  declaration in the element's type. */
                $this->type_ = $attr->getType();
            } while (false);
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
            $attrType = $this->getType();

            $converters = $this->ownerDocument->getTypeConverters();

            $converter = $converters->lookup($attrType);

            /** - If there is a specific converter for the attribute type or
             *  one of its base types, use it. */
            if (isset($converter)) {
                return $converter($this->value, $this);
            }

            /**- Otherwise, if the type is an enumeration, convert to the
             * enumerator object. */
            if ($attrType instanceof EnumerationTypeInterface) {
                return $attrType->getEnumerators()[$this->value];
            }

            /** - Otherwise, if the type is a list type, convert the value to
             * an array by splitting at whitespace. */
            if ($attrType instanceof ListType) {
                $value = preg_split('/\s+/', $this->value);

                $itemType = $attrType->getItemType();
                $converter = $converters->lookup($itemType);

                /** - If the type is a list type and there is a converter for
                 * the item type, replace the value by an associative array,
                 * mapping each item literal to its conversion result.
                 */
                if (isset($converter)) {
                    $convertedValue = [];

                    foreach ($value as $item) {
                        $convertedValue[$item] = $converter($item, $this);
                    }

                    return $convertedValue;
                }

                /** - Otherwise, if the type is a list type and the items are
                 * enumerators, replace the value by an associative array,
                 * mapping each item literal to its enumerator object.
                 *
                 * @attention This implies that repeated items will silently
                 * be dropped in the last two cases. To model such cases with
                 * possible repetition, an explicit converter for the list
                 * type is necessary.
                 */
                if ($itemType instanceof EnumerationTypeInterface) {
                    $convertedValue = [];

                    $enumerators = $itemType->getEnumerators();

                    foreach ($value as $item) {
                        $convertedValue[$item] = $enumerators[$item];
                    }

                    return $convertedValue;
                }

                return $value;
            }

            /** If none of the above applies, return the literal value. */
            return $this->value;
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
