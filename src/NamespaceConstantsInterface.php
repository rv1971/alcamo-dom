<?php

namespace alcamo\dom;

/**
 * @brief Namespace constants needed in many places
 */
interface NamespaceConstantsInterface
{
    /// Dublin core namespace
    public const DC_NS = 'http://purl.org/dc/terms/';

    /// XML Schema has Facet and Property namespace
    public const HFP_NS =
        'http://www.w3.org/2001/XMLSchema-hasFacetAndProperty';

    /// OWL Web Ontology Language namespace
    public const OWL_NS = 'http://www.w3.org/2002/07/owl#';

    /// PHP Xpath namespace
    public const PHP_XPATH_NS = 'http://php.net/xpath';

    /// RDF namespace
    public const RDF_NS = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';

    /// RDFS namespace
    public const RDFS_NS = 'http://www.w3.org/2000/01/rdf-schema#';

    /// XHTML namespace
    public const XH_NS = 'http://www.w3.org/1999/xhtml';

    /// XHTML datatypes namespace
    public const XH11D_NS = 'http://www.w3.org/1999/xhtml/datatypes/';

    /// XHTML vocabulary namespace
    public const XHV_NS = 'http://www.w3.org/1999/xhtml/vocab#';

    /// XML namespace
    public const XML_NS = 'http://www.w3.org/XML/1998/namespace';

    /// XML Schema namespace
    public const XSD_NS = 'http://www.w3.org/2001/XMLSchema';

    /// XML Schema instance namespace
    public const XSI_NS = 'http://www.w3.org/2001/XMLSchema-instance';

    /// XSL transform namespace
    public const XSL_NS = 'http://www.w3.org/1999/XSL/Transform';

    /// Map of canonical namespace prefixes
    public const NS_PRFIX_TO_NS_URI = [
        'dc'    => self::DC_NS,
        'hfp'   => self::HFP_NS,
        'owl'   => self::OWL_NS,
        'php'   => self::PHP_XPATH_NS,
        'rdf'   => self::RDF_NS,
        'rdfs'  => self::RDFS_NS,
        'xh'    => self::XH_NS,
        'xh11d' => self::XH11D_NS,
        'xhv'   => self::XHV_NS,
        'xml'   => self::XML_NS,
        'xsd'   => self::XSD_NS,
        'xsi'   => self::XSI_NS,
        'xsl'   => self::XSL_NS
    ];
}
