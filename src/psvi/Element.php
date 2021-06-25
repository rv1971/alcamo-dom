<?php

namespace alcamo\dom\psvi;

use alcamo\dom\extended\Element as BaseElement;
use alcamo\dom\schema\component\AbstractType;

class Element extends BaseElement
{
    private $type_ = false;  ///< AbstractType

    public function getType(): AbstractType
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
