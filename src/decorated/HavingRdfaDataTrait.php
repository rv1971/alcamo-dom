<?php

namespace alcamo\dom\decorated;

use alcamo\dom\ConverterPool;
use alcamo\rdfa\RdfaData;

/**
 * @brief Implementation of HavingRdfaDataInterface
 *
 * @date Last reviewed 2026-02-09
 */
trait HavingRdfaDataTrait
{
    private $rdfaData_ = false; ///< RdfaData

    public function getRdfaData(): ?RdfaData
    {
        if ($this->rdfaData_ === false) {
            $this->rdfaData_ = null;

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
                        $rdfaData = $element->createRdfaData();

                        if (isset($rdfaData)) {
                            if (isset($this->rdfaData_)) {
                                $this->rdfaData_ =
                                    $this->rdfaData_->add($rdfaData);
                            } else {
                                $this->rdfaData_ = $rdfaData;
                            }
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

            $rdfaData = RdfaData::newFromIterable(
                $rdfaDataPairs,
                $this->ownerDocument->getRdfaFactory(),
                RdfaData::URI_AS_KEY
            );

            if (isset($rdfaData)) {
                if (isset($this->rdfaData_)) {
                    $this->rdfaData_ = $this->rdfaData_->add($rdfaData);
                } else {
                    $this->rdfaData_ = $rdfaData;
                }
            }
        }

        return $this->rdfaData_;
    }
}
