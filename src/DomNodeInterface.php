<?php

namespace alcamo\dom;

use alcamo\xml\NamespaceConstantsInterface;

/**
 * @brief Common interface for DOM node classes in this namespace
 *
 * @date Last reviewed 2025-12-09
 */
interface DomNodeInterface extends
    HavingBaseUriInterface,
    NamespaceConstantsInterface,
    Rfc5147Interface,
    \Stringable
{
}
