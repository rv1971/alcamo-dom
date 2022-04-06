<?php

namespace alcamo\dom\psvi;

use alcamo\dom\extended\Attr as BaseAttr;
use alcamo\dom\schema\component\{
    ComplexType,
    EnumerationTypeInterface,
    ListType,
    SimpleTypeInterface
};

/**
 * @brief Attribute class for use in DOMDocument::registerNodeClass()
 *
 * Provides getType() to retrieve the XSD type of this attribute and uses this
 * in create Value(), so that alcamo::dom::extended::Attr::getValue() returns
 * an appropriately converted value.
 *
 * @date Last reviewed 2021-07-11
 */
class Attr extends BaseAttr
{
    private $type_;  ///< SimpleTypeInterface

    public function getType(): SimpleTypeInterface
    {
        if (!isset($this->type_)) {
            do {
                $elementType = $this->parentNode->getType();

                if (!($elementType instanceof ComplexType)) {
                    $this->type_ =
                        $this->ownerDocument->getSchema()->getAnySimpleType();
                    break;
                }

                $attr = $elementType->getAttrs()[(string)$this->getXName()]
                    ?? null;

                if (!isset($attr)) {
                    $this->type_ =
                        $this->ownerDocument->getSchema()->getAnySimpleType();
                    break;
                }

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
            && $this->parentNode->namespaceURI == Document::XSD_NS
        ) {
            return parent::createValue();
        }

        try {
            $attrType = $this->getType();

            $converters = $this->ownerDocument->getAttrConverters();

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
             * a numerically-indexed array by splitting at whitespace. */
            if ($attrType instanceof ListType) {
                $value = preg_split('/\s+/', $this->value);

                $itemType = $attrType->getItemType();
                $converter = $converters->lookup($itemType);

                /** - However, if the type is a list type and there is a
                 * converter for the item type, replace the value by an
                 * associative array, mapping each item literal to its
                 * conversion result.
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
                 * @warning This implies that repeated items will silently be
                 * dropped int he last two cases. To model such cases with
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

            /** If none of the above applies, fall back to
             *  alcamo::dom::extended::Attr::createValue(). */
            return parent::createValue();
        } catch (\Throwable $e) {
            $e->name = $this->name;
            $e->documentURI = $this->ownerDocument->documentURI;
            $e->lineNo = $this->getLineNo();

            throw $e;
        }
    }
}
