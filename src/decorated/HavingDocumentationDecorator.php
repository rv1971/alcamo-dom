<?php

namespace alcamo\dom\decorated;

use alcamo\dom\HavingDocumentationInterface;
use alcamo\rdfa\HavingRdfaDataInterface;
use alcamo\xml\NamespaceConstantsInterface;

/**
 * @brief Decorator providing documentation methods
 *
 * @date Last reviewed 2025-10-23
 */
class HavingDocumentationDecorator extends AbstractElementDecorator implements
    HavingDocumentationInterface,
    HavingRdfaDataInterface,
    NamespaceConstantsInterface
{
    use HavingDocumentationTrait;

    /// Namespaces of RDFa-relevant nodes
    public const RDFA_NSS = [
        self::DC_NS,
        self::OWL_NS,
        self::RDFS_NS,
        self::XHV_NS
    ];

    /// XPaths where metadata elements can be found
    public const APPINFO_XPATHS = [ '.', 'xsd:annotation/xsd:appinfo' ];

    /// Relative XPath to <rdfs:label> elements
    protected const RDFS_LABEL_XPATH = 'rdfs:label';
}
