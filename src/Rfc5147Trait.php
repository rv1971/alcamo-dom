<?php

namespace alcamo\dom;

/**
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
 */
trait Rfc5147Trait
{
    /// Return RFC 5147 `line=` fragment identifier
    public function getRfc5147Fragment(): string
    {
        return "line={$this->getLineNo()}";
    }

    /// Return URI with RFC 5147 `line=` fragment identifier
    public function getRfc5147Uri(): string
    {
        return
            "{$this->ownerDocument->documentURI}#{$this->getRfc5147Fragment()}";
    }
}
