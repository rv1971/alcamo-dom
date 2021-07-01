<?php

namespace alcamo\dom;

use alcamo\binary_data\BinaryString;
use alcamo\collection\ReadonlyPrefixSet;
use alcamo\exception\OutOfRange;
use alcamo\iana\MediaType;
use alcamo\ietf\{Lang, Uri};
use alcamo\range\NonNegativeRange;
use alcamo\time\Duration;
use alcamo\xml\XName;
use alcamo\xpointer\Pointer;
use Ds\Set;

/**
 * @brief Pool of converter functions for DOM node values
 *
 * Each function takes the value as their first parameter. Some take the DOM
 * node as their second one.
 *
 * @date Last reviewed 2021-07-01
 */
class ConverterPool
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
     * @brief Call DocumentFactoryInterface::createFromUrl() on the owner
     * document's document factory
     */
    public static function toDocument($value, \DOMNode $context): Document
    {
        $url = $context->resolveUri($value);

        return
            $context->ownerDocument->getDocumentFactory()->createFromUrl($url);
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
            throw new OutOfRange(
                $value,
                0,
                PHP_INT_MAX,
                '; unable to convert to integer'
            );
        }
    }

    /// Call alcamo::ietf::Lang::newFromString()
    public static function toLang($value): Lang
    {
        return Lang::newFromString($value);
    }

    /// Call alcamo::iana::MediaType::newFromString()
    public static function toMediaType($value): MediaType
    {
        return MediaType::newFromString($value);
    }

    /// Call alcamo::range::NonNegativeRange::newFromString()
    public static function toNonNegativeRange($value): NonNegativeRange
    {
        return NonNegativeRange::newFromString($value);
    }

    //// Call alcamo::collection::ReadonlyPrefixSet::newFromString()
    public static function toPrefixSet($value): ReadonlyPrefixSet
    {
        return ReadonlyPrefixSet::newFromString($value);
    }

    /// Split at whitespace
    public static function toSet($value): Set
    {
        return new Set(preg_split('/\s+/', $value));
    }

    /// Call alcamo::ietf::Uri::__construct()
    public static function toUri($value): Uri
    {
        return new Uri($value);
    }

    /// Call alcamo::xml::XName::newFromQNameAndContext()
    public static function toXName($value, \DOMNode $context): XName
    {
        return XName::newFromQNameAndContext($value, $context);
    }

    /// Split at whitespace and hanld each item as in toXName()
    public static function toXNames($value, \DOMNode $context): array
    {
        $xNames = [];

        foreach (preg_split('/\s+/', $value) as $item) {
            $xNames[] = XName::newFromQNameAndContext($item, $context);
        }

        return $xNames;
    }

    /// Convert "yes" to `true`, anything else to `false`
    public static function yesNoToBool($value): bool
    {
        return $value == 'yes';
    }

    public static function base64ToBinary($value): BinaryString
    {
        return new BinaryString(base64_decode($value));
    }

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

    /// Call alcamo::ietf::Uri::newFromCurieAndContext()
    public static function curieToUri($value, \DOMNode $context): Uri
    {
        return Uri::newFromCurieAndContext($value, $context);
    }

    /// Call alcamo::ietf::Uri::newFromSafeCurieAndContext()
    public static function safeCurieToUri($value, \DOMNode $context): Uri
    {
        return Uri::newFromSafeCurieAndContext($value, $context);
    }

    /// Call alcamo::ietf::Uri::newFromUriOrSafeCurieAndContext()
    public static function uriOrSafeCurieToUri($value, \DOMNode $context): Uri
    {
        return Uri::newFromUriOrSafeCurieAndContext($value, $context);
    }

    /// Process an XPointer URL
    public static function xPointerUrlToSubset($value, \DOMNode $context)
    {
        [ $url, $fragment ] = explode('#', $value, 2);

        $doc = $url
            ? static::toDocument($url, $context)
            : $context->ownerDocument;

        $xPointer = Pointer::newFromString($fragment);

        return $xPointer->process($doc);
    }

    /// Process an XPointer URL, returning a set of values
    public static function xPointerUrlToValueSet($value, \DOMNode $context): Set
    {
        $result = new Set();

        foreach (static::xPointerUrlToSubset($value, $context) as $node) {
            /** On Attr, call Attr::getValue() to get a value; for any other
             *  node, take the nodeValue peroperty. */
            $result->add(
                $node instanceof Attr ? $node->getValue() : $node->nodeValue
            );
        }

        return $result;
    }
}