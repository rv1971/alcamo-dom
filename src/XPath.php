<?php

namespace alcamo\dom;

/**
 * @brief Extension of DOMXPath which registers namespace prefixes
 *
 * @date Last reviewed 2025-09-15
 */
class XPath extends \DOMXPath
{
    /// Register all namepace prefixes in NamespaceConstantsInterface
    public function __construct(\DOMDocument $doc)
    {
        parent::__construct($doc);

        foreach ($doc::NS_PRFIX_TO_NS_URI as $prefix => $uri) {
            $this->registerNamespace($prefix, $uri);
        }
    }
}
