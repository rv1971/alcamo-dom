<?php

namespace alcamo\dom;

use alcamo\binary_data\BinaryString;
use alcamo\collection\ReadonlyPrefixSet;
use alcamo\iana\MediaType;
use alcamo\ietf\{Lang, Uri};
use alcamo\integer\NonNegativeRange;
use alcamo\time\Duration;
use alcamo\xml\XName;
use alcamo\xpointer\Pointer;
use Ds\Set;
use GuzzleHttp\Psr7\UriResolver;

class ConverterPool
{
    public static function toArray($value): array
    {
        return preg_split('/\s+/', $value);
    }

    public static function toBool($value): bool
    {
        return $value == 'true';
    }

    public static function toDateTime($value): \DateTime
    {
        return new \DateTime($value);
    }

    public static function toDuration($value): Duration
    {
        return new Duration($value);
    }

    public static function toFloat($value): float
    {
        return (float)$value;
    }

    /// Convert to integer if value can be represented as int
    public static function toInt($value)
    {
        if (is_int($value + 0)) {
            return (int)$value;
        } else {
            return $value;
        }
    }

    public static function toLang($value): Lang
    {
        return Lang::newFromString($value);
    }

    public static function toNonNegativeRange($value): NonNegativeRange
    {
        return NonNegativeRange::newFromString($value);
    }

    public static function toMediaType($value): MediaType
    {
        return MediaType::newFromString($value);
    }

    public static function toPrefixSet($value): ReadonlyPrefixSet
    {
        return ReadonlyPrefixSet::newFromString($value);
    }

    public static function toSet($value): Set
    {
        return new Set(preg_split('/\s+/', $value));
    }

    public static function toUri($value): Uri
    {
        return new Uri($value);
    }

    public static function toXName($value, $context): XName
    {
        return XName::newFromQNameAndContext($value, $context);
    }

    public static function toXNames($value, $context): array
    {
        $xNames = [];

        foreach (preg_split('/\s+/', $value) as $item) {
            $xNames[] = XName::newFromQNameAndContext($item, $context);
        }

        return $xNames;
    }

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

    public static function pairsToMap($value): array
    {
        $items = static::toArray($value);

        $map = [];

        for ($i = 0; isset($items[$i]); $i += 2) {
            $map[$items[$i]] = $items[$i + 1];
        }

        return $map;
    }

    public static function curieToUri($value, $context): Uri
    {
        return Uri::newFromCurieAndContext($value, $context);
    }

    public static function safeCurieToUri($value, $context): Uri
    {
        return Uri::newFromSafeCurieAndContext($value, $context);
    }

    public static function uriOrSafeCurieToUri($value, $context): Uri
    {
        return Uri::newFromUriOrSafeCurieAndContext($value, $context);
    }

    /**
     * Use the document cache if the document has an absolute URL.
     */
    public static function toDocument($value, $context): Document
    {
        $url = UriResolver::resolve(
            new Uri($context->baseURI),
            new Uri($value)
        );

        return
            $context->ownerDocument->getDocumentFactory()->createFromUrl($url);
    }

    public static function xPointerUrlToSubset($value, $context)
    {
        [ $url, $fragment ] = explode('#', $value, 2);

        $doc = $url
            ? static::toDocument($url, $context)
            : $context->ownerDocument;

        $xPointer = Pointer::newFromString($fragment);

        return $xPointer->process($doc);
    }

    public static function xPointerUrlToValueSet($value, $context): Set
    {
        $result = new Set();

        foreach (static::xPointerUrlToSubset($value, $context) as $node) {
            $result->add(
                $node instanceof Attr ? $node->getValue() : $node->nodeValue
            );
        }

        return $result;
    }
}
