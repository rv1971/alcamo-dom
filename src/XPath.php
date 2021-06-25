<?php

namespace alcamo\dom;

/// Extension of DOMXPath registering the php namespace prefix.
class XPath extends \DOMXPath
{
    public const PHP_NS = 'http://php.net/xpath'; ///< PHP namespace.

    public function __construct(\DOMDocument $doc)
    {
        parent::__construct($doc);

        $this->registerNamespace('php', self::PHP_NS);
    }
}
