<?php

namespace alcamo\dom\xh;

use alcamo\dom\ConverterPool;
use alcamo\dom\decorated\AbstractElementDecorator;
use alcamo\rdfa\RdfaData;

/**
 * @namespace alcamo::dom::xh
 *
 * XHTML-specific classes. Since this package supports use of XHTML elements
 * (such as \<xh:annotation>) within non-XHTML documents, no XHTML-specific
 * document or element class is provided. XHTML-specific functionality is
 * provided in element decorators.
 */

/**
 * @brief Decorator for \<xh:meta> elements
 *
 * @date Last reviewed 2026-02-10
 */
class MetaDecorator extends AbstractElementDecorator
{
    public function createRdfaData(): ?RdfaData
    {
        if (!isset($this->property)) {
            return null;
        }

        $literal =
            ConverterPool::toLiteral($this->content, $this->getElement());

        foreach ($this->property as $property) {
            $rdfaData[] = [ $property, $literal ];
        }

        return RdfaData::newFromIterable(
            $rdfaData,
            $this->ownerDocument->getRdfaFactory(),
            RdfaData::URI_AS_KEY
        );
    }
}
