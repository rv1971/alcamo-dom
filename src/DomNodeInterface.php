<?php

namespace alcamo\dom;

/**
 * @brief Common interface for classes to use in DOMDocument::registerNodeClass()
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
