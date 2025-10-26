<?php

namespace alcamo\dom;

/**
 * @brief Support XPath qeury() and evaluate()
 *
 * @date Last reviewed 2025-10-26
 */
interface XPathQueryableInterface
{
    /// Run DOMXPath::query()
    public function query(string $expr);

    /// Run DOMXPath::evaluate()
    public function evaluate(string $expr);
}
