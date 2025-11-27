<?php

namespace alcamo\dom;

use alcamo\binary_data\BinaryString;
use alcamo\collection\ReadonlyPrefixSet;
use alcamo\exception\{OutOfRange, SyntaxError};
use alcamo\range\NonNegativeRange;
use alcamo\rdfa\{Lang, MediaType};
use alcamo\time\Duration;
use alcamo\uri\{Uri, UriFromCurieFactory};
use alcamo\xml\XName;
use alcamo\xpointer\Pointer;
use Ds\Set;

/**
 * @brief Pool of converter functions for DOM node values
 *
 * Each function takes the value as its first parameter. Some take the DOM
 * node as their second one.
 *
 * @date Last reviewed 2021-07-01
 */
class ConverterPool implements NamespaceConstantsInterface
{
    /// Split at whitespace
    public static function toArray($value): array
    {
        return preg_split('/\s+/', $value);
    }

    /// Convert "true" to `true`, anything else to `false`
    public static function toBool($value): bool
    {
        return $value == 'true';
    }

    /// Call [DateTime::__construct()](https://www.php.net/manual/en/datetime.construct)
    public static function toDateTime($value): \DateTime
    {
        return new \DateTime($value);
    }

    /**
     * @brief Call createFromUri() on the owner document's document factory
     *
     * @param $context must implement HavingBaseUriInterface
     */
    public static function toDocument($value, \DOMNode $context): Document
    {
        $uri = $context->resolveUri($value);

        return
            $context->ownerDocument->getDocumentFactory()->createFromUri($uri);
    }

    /// Call alcamo::time::Duration::__construct()
    public static function toDuration($value): Duration
    {
        return new Duration($value);
    }

    /// Convert to float
    public static function toFloat($value): float
    {
        return (float)$value;
    }

    /// Convert to integer
    public static function toInt($value): int
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
    public static function toAllNNI($value): int
    {
        return $value == 'unbounded' ? -1 : (int)$value;
    }

    /// Split at whitespace, return set of integers
    public static function toIntSet($value): Set
    {
        $result = new Set();

        foreach (preg_split('/\s+/', $value) as $item) {
            $result->add((int)$item);
        }

        return $result;
    }

    /// Call alcamo::rdfa::Lang::newFromString()
    public static function toLang($value): Lang
    {
        return Lang::newFromString($value);
    }

    /// Call alcamo::rdfa::MediaType::newFromString()
    public static function toMediaType($value): MediaType
    {
        return MediaType::newFromString($value);
    }

    /// Call alcamo::range::NonNegativeRange::newFromString()
    public static function toNonNegativeRange($value): NonNegativeRange
    {
        return NonNegativeRange::newFromString($value);
    }

    /// Call alcamo::collection::ReadonlyPrefixSet::newFromString()
    public static function toPrefixSet($value): ReadonlyPrefixSet
    {
        return ReadonlyPrefixSet::newFromString($value);
    }

    /// Call alcamo::collection::ReadonlyPrefixBlackWhiteList::newFromStringWithOperator()
    public static function toPrefixBlackWhiteList(
        $value
    ): ReadonlyPrefixBlackWhiteList {
        return
            ReadonlyPrefixBlackWhiteList::newFromStringWithOperator($value);
    }

    /// Split at whitespace
    public static function toSet($value): Set
    {
        return $value == '' ? new Set() : new Set(preg_split('/\s+/', $value));
    }

    /// Call alcamo::uri::Uri::__construct()
    public static function toUri($value): Uri
    {
        return new Uri($value);
    }

    /// Call alcamo::xml::XName::newFromQNameAndContext()
    public static function toXName($value, \DOMNode $context): XName
    {
        return XName::newFromQNameAndContext($value, $context);
    }

    /// Split at whitespace and handle each item as in toXName()
    public static function toXNames($value, \DOMNode $context): array
    {
        $xNames = [];

        foreach (preg_split('/\s+/', $value) as $item) {
            $xNames[] = XName::newFromQNameAndContext($item, $context);
        }

        return $xNames;
    }

    /// Resolve an ID to the DOM element it references
    public static function resolveIdRef($value, \DOMNode $context): \DOMElement
    {
        return $context->ownerDocument->getElementById($value);
    }

    /// Convert "yes" to `true`, anything else to `false`
    public static function yesNoToBool($value): bool
    {
        return $value == 'yes';
    }

    /// Convert base64 data to binary data
    public static function base64ToBinary($value): BinaryString
    {
        return new BinaryString(base64_decode($value));
    }

    /// Convert hex data to binary data
    public static function hexToBinary($value): BinaryString
    {
        return BinaryString::newFromHex($value);
    }

    /// Transform a value such as in `xsi:schemaLocation` to a map
    public static function pairsToMap($value): array
    {
        $items = static::toArray($value);

        $map = [];

        for ($i = 0; isset($items[$i]); $i += 2) {
            $map[$items[$i]] = $items[$i + 1];
        }

        return $map;
    }

    /// Call alcamo::uri::UriFromCurieFactory::createFromCurieAndContext()
    public static function curieToUri($value, \DOMNode $context): Uri
    {
        return (new UriFromCurieFactory())
            ->createFromCurieAndContext($value, $context);
    }

    /// Call alcamo::uri::UriFromCurieFactory::createFromSafeCurieAndContext()
    public static function safeCurieToUri($value, \DOMNode $context): Uri
    {
        return (new UriFromCurieFactory())
            ->createFromSafeCurieAndContext($value, $context);
    }

    /// Call alcamo::uri::UriFromCurieFactory::createFromUriOrSafeCurieAndContext()
    public static function uriOrSafeCurieToUri($value, \DOMNode $context): Uri
    {
        return (new UriFromCurieFactory())
            ->createFromUriOrSafeCurieAndContext($value, $context);
    }

    /// Call alcamo::uri::UriFromCurieFactory::createFromCurieAndContext()
    public static function xhRelToUri($value, \DOMNode $context): Uri
    {
        return (new UriFromCurieFactory())
            ->createFromCurieAndContext($value, $context, self::XHV_NS);
    }

    /// Process an XPointer URI
    public static function xPointerUriToSubset($value, \DOMNode $context)
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
    public static function xPointerUriToValueSet($value, \DOMNode $context): Set
    {
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
}
