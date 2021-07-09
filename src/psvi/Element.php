<?php

namespace alcamo\dom\psvi;

use alcamo\dom\extended\Element as BaseElement;
use alcamo\dom\schema\component\TypeInterface;

class Element extends BaseElement
{
    private $type_ = false;  ///< TypeInterface

    public function getType(): TypeInterface
    {
        if ($this->type_ === false) {
            $schema = $this->ownerDocument->getSchema();

            $this->type_ = $schema->lookupElementType($this);

            if (!isset($this->type_)) {
                $this->type_ = $schema->getAnyType();
            }
        }

        return $this->type_;
    }
}
