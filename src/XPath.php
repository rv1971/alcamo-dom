<?php

namespace alcamo\dom;

/**
 * @brief Extension of DOMXPath which registers namespace prefixes
 *
 * @date Last reviewed 2025-09-15
 */
class XPath extends \DOMXPath
{
    /// Register all namepace prefixes in the document class
    public function __construct(\DOMDocument $doc)
    {
        parent::__construct($doc);

        foreach ($doc::NS_PRFIX_TO_NS_NAME as $prefix => $uri) {
            $this->registerNamespace($prefix, $uri);
        }
    }
}
