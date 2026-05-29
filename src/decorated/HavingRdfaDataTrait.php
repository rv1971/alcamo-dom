<?php

namespace alcamo\dom\decorated;

use alcamo\dom\ConverterPool;
use alcamo\rdfa\{ImmutableRdfaData, RdfaData};

/**
 * @brief Implementation of HavingRdfaDataInterface
 *
 * @date Last reviewed 2026-02-09
 */
trait HavingRdfaDataTrait
{
    private $rdfaData_ = false; ///< ImmutableRdfaData

    public function getRdfaData(): ImmutableRdfaData
    {
        if ($this->rdfaData_ === false) {
            $literalFactory = $this->ownerDocument->getLiteralFactory();

            $rdfaDataPairs = [];

            /** Create RDFa data from:
             * - Attributes with relevant namespace
             */
            foreach ($this->attributes as $attr) {
                if (
                    !isset($attr->namespaceURI)
                        || !in_array($attr->namespaceURI, static::RDFA_NSS)
                ) {
                    continue;
                }

                $rdfaDataPairs[] = [
                    "{$attr->namespaceURI}{$attr->localName}",
                    $literalFactory
                        ->create($attr->getValue(), null, $attr->getLang())
                ];
            }

            foreach (static::APPINFO_XPATHS as $appinfoPath) {
                foreach (
                    $this->query(
                        "$appinfoPath/*[namespace-uri() != '']"
                    ) as $element
                ) {
                    /**
                     * - HTML \<meta> and \<link> elements in APPINFO_PATHS
                     */
                    if (
                        $element->namespaceURI == self::XH_NS
                            && ($element->localName == 'link'
                                || $element->localName == 'meta')
                    ) {
                        $elementRdfaData = $element->createRdfaData();

                        if (isset($rdfaData)) {
                            $rdfaData->add($elementRdfaData);
                        } else {
                            $rdfaData = $elementRdfaData;
                        }

                        continue;
                    }

                    if (!in_array($element->namespaceURI, static::RDFA_NSS)) {
                        continue;
                    }

                    /**
                     * - RDF child elements in APPINFO_PATHS
                     */
                    $rdfaDataPairs[] = [
                        "{$element->namespaceURI}{$element->localName}",
                        ConverterPool::toLiteral($element->nodeValue, $element)
                    ];
                }
            }

            if ($rdfaDataPairs) {
                $rdfaData2 = RdfaData::newFromIterable(
                    $rdfaDataPairs,
                    $this->ownerDocument->getRdfaFactory(),
                    RdfaData::URI_AS_KEY
                );

                if (isset($rdfaData)) {
                    $rdfaData->add($rdfaData2);
                } else {
                    $rdfaData = $rdfaData2;
                }
            }

            $this->rdfaData_ = isset($rdfaData)
                ? $rdfaData->toImmutable()
                : ImmutableRdfaData::newEmpty();
        }

        return $this->rdfaData_;
    }
}
