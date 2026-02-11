<?php

namespace alcamo\dom;

use alcamo\binary_data\BinaryString;
use alcamo\collection\ReadonlyPrefixSet;
use alcamo\dom\extended\DomNodeInterface as ExtendedDomNodeInterface;
use alcamo\dom\psvi\Document as PsviDocument;
use alcamo\dom\schema\{Converter, SchemaFactory, TargetNsCache, TypeMap};
use alcamo\exception\{OutOfRange, SyntaxError};
use alcamo\range\NonNegativeRange;
use alcamo\rdfa\{Lang, LiteralFactory, LiteralInterface, Node, MediaType};
use alcamo\time\Duration;
use alcamo\uri\{Uri, UriFromCurieFactory};
use alcamo\xml\{NamespaceConstantsInterface, XName};
use alcamo\xpointer\Pointer;
use Ds\Set;
use Psr\Http\Message\UriInterface;

/**
 * @brief Pool of converter functions for DOM node values
 *
 * Each function takes the value as its first parameter. Some take the DOM
 * node as their second one. It is deliberate that the second one is mostly of
 * type \DOMNode and only in some cases of more advanced types such as
 * alcamo::dom::DomNodeInterface, to be just as restrictive as necessary, not
 * more.
 *
 * @date Last reviewed 2021-07-01
 */
class ConverterPool implements NamespaceConstantsInterface
{
    /// Split at whitespace
    public static function toArray(string $value): array
    {
        return preg_split('/\s+/', $value);
    }

    /// Convert "true" to `true`, anything else to `false`
    public static function toBool(string $value): bool
    {
        return $value == 'true';
    }

    /// Call [DateTime::__construct()](https://www.php.net/manual/en/datetime.construct)
    public static function toDateTime(string $value): \DateTime
    {
        return new \DateTime($value);
    }

    /**
     * @brief Call createFromUri() on the owner document's document factory
     *
     * Identical to toDocumentOrElement() except for the restriction on the
     * return type.
     */
    public static function toDocument(
        string $value,
        DomNodeInterface $context
    ): Document {
        return $context->ownerDocument->getDocumentFactory()->createFromUri(
            $context->resolveUri($value)
        );
    }

    /**
     * @brief Call createFromUri() on the owner document's document factory
     */
    public static function toDocumentOrElement(
        string $value,
        DomNodeInterface $context
    ): DomNodeInterface {
        return $context->ownerDocument->getDocumentFactory()->createFromUri(
            $context->resolveUri($value)
        );
    }

    /// Call alcamo::time::Duration::__construct()
    public static function toDuration(string $value): Duration
    {
        return new Duration($value);
    }

    /// Convert to float
    public static function toFloat(string $value): float
    {
        return (float)$value;
    }

    /// Convert to integer
    public static function toInt(string $value): int
    {
        if (is_int($value + 0)) {
            return (int)$value;
        } else {
            /** @throw alcamo::exception::OutOfRange if $value is too large to
             *  be represented as an integer. */
            throw (new OutOfRange())->setMessageContext(
                [
                    'value' => $value,
                    'lowerBound' => PHP_INT_MIN,
                    'upperBound' => PHP_INT_MAX,
                    'extraMessage' => 'unable to convert to integer'
                ]
            );
        }
    }

    /// To integer, return -1 for "unbounded"
    public static function toAllNNI(string $value): int
    {
        return $value == 'unbounded' ? -1 : (int)$value;
    }

    /// Split at whitespace, return set of integers
    public static function toIntSet(string $value): Set
    {
        $result = new Set();

        foreach (preg_split('/\s+/', $value) as $item) {
            $result->add((int)$item);
        }

        return $result;
    }

    /// Call alcamo::rdfa::Lang::newFromString()
    public static function toLang(string $value): Lang
    {
        return Lang::newFromString($value);
    }

    /**
     * @brief Create an RDF literal object
     *
     * If the context (or its parent, if the context is not an element node)
     * is an xhtml element and has a `datatype` attribute, use it for the
     * datatype URI. Use the applicable language identifcation, if any.
     */
    public static function toLiteral(
        string $value,
        ExtendedDomNodeInterface $context
    ): LiteralInterface {
        $element =
            $context instanceof \DOMElement ? $context : $context->parentNode;

        return $context->ownerDocument->getLiteralFactory()->create(
            $value,
            $element->namespaceURI == (self::XH_NS && $element->datatype)
                ? $element->datatype
                : null,
            $element->getLang()
        );
    }

    /// Call alcamo::rdfa::MediaType::newFromString()
    public static function toMediaType(string $value): MediaType
    {
        return MediaType::newFromString($value);
    }

    /// Call alcamo::range::NonNegativeRange::newFromString()
    public static function toNonNegativeRange(string $value): NonNegativeRange
    {
        return NonNegativeRange::newFromString($value);
    }

    /// Call alcamo::collection::ReadonlyPrefixSet::newFromString()
    public static function toPrefixSet(string $value): ReadonlyPrefixSet
    {
        return ReadonlyPrefixSet::newFromString($value);
    }

    /// Call alcamo::collection::ReadonlyPrefixBlackWhiteList::newFromStringWithOperator()
    public static function toPrefixBlackWhiteList(
        string $value
    ): ReadonlyPrefixBlackWhiteList {
        return
            ReadonlyPrefixBlackWhiteList::newFromStringWithOperator($value);
    }

    /**
     * @brief Create an RDF node object
     *
     * If the context (or its parent, if the context is not an element node)
     * is an xhtml, use any `hreflang` attribute for the `dc:language`
     * property, `title` for `dc:title` and `type` for `dc:format` of the
     * indicated resource.
     */
    public static function toRdfaNode(
        string $value,
        DomNodeInterface $context
    ): Node {
        $element =
            $context instanceof \DOMElement ? $context : $context->parentNode;

        if ($element->namespaceURI == self::XH_NS) {
            $rdfaData = [];

            if ($element->hasAttribute('hreflang')) {
                $rdfaData[] =
                    [ 'dc:language', $element->getAttribute('hreflang') ];
            }

            if ($element->hasAttribute('title')) {
                $rdfaData[] = [ 'dc:title', $element->getAttribute('title') ];
            }

            if ($element->hasAttribute('type')) {
                $rdfaData[] = [ 'dc:format', $element->getAttribute('type') ];
            }
        } else {
            $rdfaData = null;
        }

        return new Node($value, $rdfaData);
    }

    /// Split at whitespace
    public static function toSet(string $value): Set
    {
        return $value == '' ? new Set() : new Set(preg_split('/\s+/', $value));
    }

    /// Call alcamo::uri::Uri::__construct()
    public static function toUri(string $value): Uri
    {
        return new Uri($value);
    }

    /// Call alcamo::xml::XName::newFromQNameAndContext()
    public static function toXName(string $value, \DOMNode $context): XName
    {
        return XName::newFromQNameAndContext($value, $context);
    }

    /// Split at whitespace and handle each item as in toXName()
    public static function toXNames(string $value, \DOMNode $context): array
    {
        $xNames = [];

        foreach (preg_split('/\s+/', $value) as $item) {
            $xNames[] = XName::newFromQNameAndContext($item, $context);
        }

        return $xNames;
    }

    /// Resolve an ID to the DOM element it references
    public static function resolveIdRef(string $value, \DOMNode $context): \DOMElement
    {
        return $context->ownerDocument->getElementById($value);
    }

    /// Convert "yes" to `true`, anything else to `false`
    public static function yesNoToBool(string $value): bool
    {
        return $value == 'yes';
    }

    /// Convert base64 data to binary data
    public static function base64ToBinary(string $value): BinaryString
    {
        return new BinaryString(base64_decode($value));
    }

    /// Convert hex data to binary data
    public static function hexToBinary(string $value): BinaryString
    {
        return BinaryString::newFromHex($value);
    }

    /// Transform a value such as in `xsi:schemaLocation` to a map
    public static function pairsToMap(string $value): array
    {
        $items = static::toArray($value);

        $map = [];

        for ($i = 0; isset($items[$i]); $i += 2) {
            $map[$items[$i]] = $items[$i + 1];
        }

        return $map;
    }

    /// Call alcamo::uri::UriFromCurieFactory::createFromCurieAndContext()
    public static function curieToUri(string $value, \DOMNode $context): Uri
    {
        return (new UriFromCurieFactory())
            ->createFromCurieAndContext($value, $context);
    }

    /// Convert list of CURIEs to array of URIs
    public static function curiesToUris(string $value, \DOMNode $context): array
    {
        $uris = [];

        $uriFactory = new UriFromCurieFactory();

        foreach (self::toArray($value) as $curie) {
            $uris[] = $uriFactory->createFromCurieAndContext($curie, $context);
        }

        return $uris;
    }

    /// Call alcamo::uri::UriFromCurieFactory::createFromCurieAndContext()
    public static function curieToAbsUri(
        string $value,
        \DOMNode $context
    ): UriInterface {
        return $context->resolveUri(
            (new UriFromCurieFactory())
                ->createFromCurieAndContext($value, $context)
        );
    }

    /// Convert list of CURIEs to array of absolute URIs
    public static function curiesToAbsUris(
        string $value,
        \DOMNode $context
    ): array {
        $uris = [];

        $uriFactory = new UriFromCurieFactory();

        foreach (self::toArray($value) as $curie) {
            $uris[] = $context->resolveUri(
                $uriFactory->createFromCurieAndContext($curie, $context)
            );
        }

        return $uris;
    }

    /// Call alcamo::uri::UriFromCurieFactory::createFromSafeCurieAndContext()
    public static function safeCurieToUri(string $value, \DOMNode $context): Uri
    {
        return (new UriFromCurieFactory())
            ->createFromSafeCurieAndContext($value, $context);
    }

    /// Call alcamo::uri::UriFromCurieFactory::createFromUriOrSafeCurieAndContext()
    public static function uriOrSafeCurieToUri(string $value, \DOMNode $context): Uri
    {
        return (new UriFromCurieFactory())
            ->createFromUriOrSafeCurieAndContext($value, $context);
    }

    /// Call alcamo::uri::UriFromCurieFactory::createFromCurieAndContext()
    public static function xhRelToUri(
        string $value,
        \DOMNode $context
    ): UriInterface {
        return $context->resolveUri(
            (new UriFromCurieFactory())
                ->createFromCurieAndContext($value, $context, self::XHV_NS)
        );
    }

    /// Convert list of XHTML relation CURIEs to array of URIs
    public static function xhRelsToUris(string $value, \DOMNode $context): array
    {
        $uris = [];

        $uriFactory = new UriFromCurieFactory();

        foreach (self::toArray($value) as $curie) {
            $uris[] = $context->resolveUri(
                $uriFactory
                    ->createFromCurieAndContext($curie, $context, self::XHV_NS)
            );
        }

        return $uris;
    }

    /// Process an XPointer URI
    public static function xPointerUriToSubset(string $value, \DOMNode $context)
    {
        $a = explode('#', $value, 2);

        if (!isset($a[1])) {
            throw (new SyntaxError())->setMessageContext(
                [
                    'inData' => $value,
                    'atUri' => $context->ownerDocument->documentURI,
                    'atLine' => $context->getLineNo()
                ]
            );
        }

        [ $uri, $fragment ] = $a;

        $doc = $uri
            ? static::toDocument($uri, $context)
            : $context->ownerDocument;

        $xPointer = Pointer::newFromString($fragment);

        return $xPointer->process($doc);
    }

    /// Process an XPointer URI, returning the set of the values of the nodes found
    public static function xPointerUriToValueSet(
        string $value,
        \DOMNode $context
    ): Set {
        $result = new Set();

        $nodes = static::xPointerUriToSubset($value, $context);
        if (isset($nodes)) {
            foreach ($nodes as $node) {
                /** On Attr, call Attr::getValue() to get a value; for any
                 *  other node, take the nodeValue property. */
                $result->add(
                    $node instanceof Attr ? $node->getValue() : $node->nodeValue
                );
            }
        }

        return $result;
    }

    /**
     * @brief Convert according to RDFa datatype, if given
     *
     * This implementation uses the namespace mappings in scope to resolve any
     * CURIE in the `datatype` attribute, coherently with in
     * [RDFa 1.0](https://www.w3.org/TR/rdfa-syntax/) and
     * [CURIE Syntax 1.0](https://www.w3.org/2001/sw/BestPractices/HTML/2005-10-27-CURIE).
     * This is basically incompatible with
     * [RDFa 1.1](https://www.w3.org/TR/rdfa-core/)
     * which uses an RDFa-specific prefix mechanism. The latter is meant for
     * HTML documents an unsuitable for XML documents containing embedded HTML
     * code.
     *
     * However, XSD Schema built-in datatypes such as `xsd:date` have the same
     * effect in RDFa 1.1, RDFa 1.0 and the current implementation:
     * - They work in RDFa 1.1 because the prefix `xsd` is contained in the
     *   [RDFa Core Initial Context](https://www.w3.org/2011/rdfa-context/rdfa-1.1).

     * - They work in RDFa 1.0 because the RDFa 1.0 specification gives such
     *   examples, even though it is unclear *how* this works because the
     *   CURIE resolution mechanism described in RDFa 1.0 implies that
     *   `xsd:dateTime` in the given examples would resolve to
     *   `http://www.w3.org/2001/XMLSchemadateTime` rather than
     *   `http://www.w3.org/2001/XMLSchema#dateTime`.
     * - They work in the present implementation because
     *   alcamo::uri::UriFromCurieFactory::createFromNsNameAndLocalName()
     *   inserts a `#` in such cases.
     */
    public static function toRdfaDatatype(
        string $value,
        DomNodeInterface $context
    ) {
        /** Take the datatype URI from the `datatype` attribute of $context
         *  (or $context's parent node if $context is an attribute), if
         *  present. */

        $element =
            $context instanceof \DOMAttr ? $context->parentNode : $context;

        if (!isset($element->datatype)) {
            return $value;
        }

        $typeXName = TargetNsCache::getInstance()
            ->typeUriToTypeXName($element->datatype);

        /** Look for a converter in the $context document, if it is of type
         *  alcamo::dom::psvi::Document, otherwise use the builtin converter
         *  in alcamo::dom::schema::Converter. */

        return ($element->ownerDocument instanceof PsviDocument
                ? $element->ownerDocument->getConverter()
                : Converter::getBuiltinConverter())
            ->convert($value, $context, $typeXName);
    }
}
