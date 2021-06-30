<?php

namespace alcamo\dom;

/**
 * @brief Extension of DOMXPath whixch registers the php namespace prefix
 *
 * @date Last reviewed 2021-06-30
 */
class XPath extends \DOMXPath
{
    public const PHP_NS = 'http://php.net/xpath'; ///< PHP namespace

    /// Register the prefix `php` for @ref PHP_NS
    public function __construct(\DOMDocument $doc)
    {
        parent::__construct($doc);

        $this->registerNamespace('php', self::PHP_NS);
    }
}
