<?php

namespace alcamo\dom\extended;

use alcamo\dom\Element as BaseElement;

class Element extends BaseElement
{
    use MagicAttrAccessTrait;
    use HasLangTrait;
    use RegisteredNodeTrait;
}
