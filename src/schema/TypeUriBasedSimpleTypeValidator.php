<?php

namespace alcamo\dom\schema;

use alcamo\dom\extended\DocumentFactory;
use alcamo\dom\xsd\Document;
use alcamo\exception\Unsupported;
use alcamo\ietf\Uri;
use GuzzleHttp\Psr7\UriResolver;

class TypeUriBasedSimpleTypeValidator extends AbstractSimpleTypeValidator
{
    private $baseUrl_; ///< Uri

    /**
     * @param $baseUrl Uri Base URL to resolve the type URIs to absolute
     * ones. This allows to use the cache for XSDs.
     */
    public function __construct(Uri $baseUrl = null)
    {
        $this->baseUrl_ = $baseUrl ?? new Uri();
    }

    public function getBaseUrl(): Uri
    {
        return $this->baseUrl_;
    }

    /*
     * @param $valueTypeUriPairs iterable Pairs consisting of a value and the
     * URI of a type.
     */
    public function validate($valueTypeUriPairs): array
    {
        $nsName2schemaLocation = [];

        $valueTypeXNamePairs = [];

        foreach ($valueTypeUriPairs as $valueTypeUriPair) {
            [ $value, $typeUri ] = $valueTypeUriPair;
            [ $schemaLocation, $typeId ] = explode('#', $typeUri, 2);

            if (strpos($typeId, '(') !== false) {
                /** @throw Unsupported when attempting to use a pointer that
                 *  is not an id. */
                throw new Unsupported('Non-id pointers to XSD types');
            }

            $url = new Uri($schemaLocation);

            if (!Uri::isAbsolute($url)) {
                $url = UriResolver::resolve($this->baseUrl_, $url);
            }

            $xsd = (new DocumentFactory())
                ->createFromUrl($url, Document::class, null, true);

            $nsName = $xsd->documentElement->targetNamespace;

            $nsName2schemaLocation[$nsName] = $url;

            $valueTypeXNamePairs[] =
                [ $value, $xsd[$typeId]->getComponentXName() ];
        }

        return self::validateAux(
            $valueTypeXNamePairs,
            self::createXsdText($nsName2schemaLocation)
        );
    }
}
