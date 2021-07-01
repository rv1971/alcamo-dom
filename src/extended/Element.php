<?php

namespace alcamo\dom\extended;

use alcamo\dom\Element as BaseElement;

/**
 * @brief Element class for use in DOMDocument::registerNodeClass()
 *
 * Uses MagicAttrAccessTrait to provide attributes as magic properties,
 * potentially convertig the literal value from the XML data into some other
 * type.
 *
 * @date Last reviewed 2021-07-01
 */
class Element extends BaseElement
{
    use MagicAttrAccessTrait;
    use HasLangTrait;
    use RegisteredNodeTrait;
}
