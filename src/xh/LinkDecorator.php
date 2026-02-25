<?php

namespace alcamo\dom\xh;

use alcamo\dom\ConverterPool;
use alcamo\dom\decorated\AbstractElementDecorator;
use alcamo\rdfa\RdfaData;

/**
 * @brief Decorator for \<xh:link> elements
 *
 * @date Last reviewed 2026-02-10
 */
class LinkDecorator extends AbstractElementDecorator
{
    public function createRdfaData(): ?RdfaData
    {
        $node = ConverterPool::toRdfaNode(
            $this->href ?? $this->resource,
            $this->getElement()
        );

        foreach ($this->rel as $rel) {
            $rdfaData[] = [ $rel, $node ];
        }

        return RdfaData::newFromIterable(
            $rdfaData,
            $this->ownerDocument->getRdfaFactory(),
            RdfaData::URI_AS_KEY
        );
    }
}
