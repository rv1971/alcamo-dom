<?php

namespace alcamo\dom;

/**
 * @brief Provide RFC 5147 data
 *
 * RFC 5147 defines fragment identifiers for text/plain. Even though XML is
 * not text/plain, the line fragment identifier defined there can usefully be
 * applied to XML documents if the document is split into lines. A line in an
 * XML document is always inside a node (at least the document itself), so a
 * line position can be considered as denoting the innermost nodes that
 * intersect this line. This provides a simple human- and machine-readable
 * identifier, which may be a unqiue identifier for a node depending on how
 * the XML text is formatted.
 *
 * @sa [RFC 5147](https://datatracker.ietf.org/doc/html/rfc5147)
 *
 * @date Last reviewed 2025-09-15
 */
trait Rfc5147Trait
{
    public function getRfc5147Fragment(): string
    {
        $lineNo = $this->getLineNo();
        return 'line=' . ($lineNo - 1) . ',' . $lineNo;
    }

    public function getRfc5147Uri(): string
    {
        return
            "{$this->ownerDocument->documentURI}#{$this->getRfc5147Fragment()}";
    }
}
