<?php

namespace alcamo\dom\extended;

use alcamo\dom\Element as BaseElement;

/**
 * @brief Element class for use in DOMDocument::registerNodeClass()
 *
 * @date Last reviewed 2025-11-05
 */
class Element extends BaseElement
{
    use HavingLangTrait;
    use MagicAttrAccessTrait;
}
