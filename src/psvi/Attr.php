<?php

namespace alcamo\dom\psvi;

use alcamo\dom\extended\Attr as BaseAttr;
use alcamo\dom\schema\component\{
    ComplexType,
    EnumerationTypeInterface,
    ListType,
    SimpleTypeInterface
};

class Attr extends BaseAttr
{
    private $type_;  ///< SimpleTypeInterface

    public function getType(): SimpleTypeInterface
    {
        if (!isset($this->type_)) {
            do {
                $elementType = $this->parentNode->getType();

                if (!($elementType instanceof ComplexType)) {
                    break;
                }

                $attr = $elementType->getAttrs()[(string)$this->getXName()]
                    ?? null;

                if (!isset($attr)) {
                    break;
                }

                $this->type_ = $attr->getType();
            } while (false);

            if (!isset($this->type_)) {
                $this->type_ =
                    $this->ownerDocument->getSchema()->getAnySimpleType();
            }
        }

        return $this->type_;
    }

    protected function createValue()
    {
        try {
            $attrType = $this->getType();

            $converters = $this->ownerDocument->getAttrConverters();

            $converter = $converters->lookup($attrType);

            /* If there is a specific converter, use it. */
            if (isset($converter)) {
                return $converter($this->value, $this);
            }

            /* Otherwise, if the type is a list type, convert the value to a
             * numerically-indexed array. */
            if ($attrType instanceof ListType) {
                $value = preg_split('/\s+/', $this->value);

                $itemType = $attrType->getItemType();
                $converter = $converters->lookup($itemType);

                /* In particular, if the type is a list type and there is a
                 * converter for the item type, replace the value by an
                 * associative array, mapping each item literal to its
                 * conversion result.
                 *
                 * @warning This implies that repeated items will silently be
                 * dropped in this case. To model lists of items having a
                 * converter with possible repetition, an explicit converter
                 * for the list type is necessary.
                 */
                if (isset($converter)) {
                    $convertedValue = [];

                    foreach ($value as $item) {
                        $convertedValue[$item] = $converter($item, $this);
                    }

                    return $convertedValue;
                }

                /* Otherwise, if the type is a list type and the items are
                 * enumerators, replace the value by an associative array,
                 * mapping each item literal to its enumerator object.
                 *
                 * @warning This implies that repeated enumerators will
                 * silently be dropped. To model lists of enumerators with
                 * possible repetition, an explicit converter for the list
                 * type is necessary.
             */
                if ($itemType instanceof EnumerationTypeInterface) {
                    $convertedValue = [];

                    foreach ($value as $item) {
                        $convertedValue[$item] = $itemType->getEnumerators()[$item];
                    }

                    return $convertedValue;
                }

                return $value;
            }

            return parent::createValue();
        } catch (\Throwable $e) {
            $e->name = $this->name;
            $e->documentURI = $this->ownerDocument->documentURI;
            $e->lineNo = $this->getLineNo();

            throw $e;
        }
    }
}
