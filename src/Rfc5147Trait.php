<?php

namespace alcamo\dom;

/**
 * @brief Provide RFC 5147 data
 *
 * RFC 5147 defines fragment identifiers for text/plain. Even though XML is
 * not text/plain, the line fragment identifier defined there can usefully be
 * applied to XML documents if the document is split into lines. This provides
 * a simple human- and machine-readable identifier, which *may* be a unqiue
 * identifier for a node depending on how the XML text is formatted.
 *
 * A node can span over multiple lines and there is no way to tell which ones,
 * hence it is not possible to return a minimal *range* in the sense of RFC
 * 5147 which is known to contain the node. Therefore, this implementation
 * provides the position of the beginning of the line indicated by
 * DOMNode::getLineNo(). For instance:
 * - If the XML declaration is on the first line of the file,
 *   getRfc5147Fragment() will return `line=0` for the DOMDocument.
 * - If there is an element contained in the 3rd line of the file,
 *   getRfc5147Fragment() will return `line=2` for that element.
 *
 * Tests show that DOMNode::getLineNo() seems to return the last line of the
 * opening tag of an element. But this is not documented in the PHP manual.
 *
 * @sa [RFC 5147](https://datatracker.ietf.org/doc/html/rfc5147)
 *
 * @date Last reviewed 2025-09-15
 */
trait Rfc5147Trait
{
    /** @copydoc alcamo::dom::Rfc5147Interface::getRfc5147Fragment() */
    public function getRfc5147Fragment(): string
    {
        return 'line=' . ($this->getLineNo() - 1);
    }

    /** @copydoc alcamo::dom::Rfc5147Interface::getRfc5147Uri() */
    public function getRfc5147Uri(): string
    {
        return
            "{$this->ownerDocument->documentURI}#{$this->getRfc5147Fragment()}";
    }
}
