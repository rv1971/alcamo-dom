<?php

namespace alcamo\dom\schema;

use alcamo\dom\decorated\{Document, DocumentFactory};
use alcamo\exception\Unsupported;
use alcamo\uri\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Psr\Http\Message\UriInterface;

/**
 * @brief Class that validates data of some XSD simple type given by a URI
 *
 * Supported are URIs that follow the id solution in [XML Schema Datatypes in
 * RDF and OWL](https://www.w3.org/TR/swbp-xsch-datatypes)
 *
 * @date Last reviewed 2021-07-11
 */
class TypeUriBasedSimpleTypeValidator extends AbstractSimpleTypeValidator
{
    private $baseUrl_; ///< Uri

    /**
     * @param $baseUrl Uri Base URL to resolve the type URIs to absolute
     * ones. This allows to use the cache for XSDs.
     */
    public function __construct(UriInterface $baseUrl = null)
    {
        $this->baseUrl_ = $baseUrl ?? new Uri();
    }

    public function getBaseUrl(): UriInterface
    {
        return $this->baseUrl_;
    }

    /*
     * @param $valueTypeUriPairs Pairs consisting of a value and the URI of a
     * type.
     */
    public function validate($valueTypeUriPairs): array
    {
        /* Array of maps mapping namespace names to schema locations. */
        $nsNameToSchemaLocations = [];

        /* Array of maps mapping values to XNames. */
        $valueTypeXNamePairMaps = [];

        foreach ($valueTypeUriPairs as $pair) {
            [ $value, $typeUri ] = $pair;
            [ $schemaLocation, $typeId ] = explode('#', $typeUri, 2);

            if (strpos($typeId, '(') !== false) {
                /** @throw alcamo::exception::Unsupported when attempting to
                 *  use a pointer that is not an id. */
                throw (new Unsupported())->setMessageContext(
                    [ 'feature' => 'Non-id pointers to XSD types' ]
                );
            }

            $url = new Uri($schemaLocation);

            if (!Uri::isAbsolute($url)) {
                $url = UriResolver::resolve($this->baseUrl_, $url);
            }

            $xsd = (new DocumentFactory())
                ->createFromUrl($url, Document::class, null, true);

            $nsName = $xsd->documentElement->targetNamespace;

            /* Store the mapping of namespace name to schema location in a map
             * so that it does not conflict with other schema locations for
             * the same namespace. */
            for ($i = 0;; $i++) {
                if (!isset($nsNameToSchemaLocations[$i])) {
                    $nsNameToSchemaLocations[$i] = [];
                }

                if (!isset($nsNameToSchemaLocations[$i][$nsName])) {
                    $nsNameToSchemaLocations[$i][$nsName] = $url;
                    break;
                }

                if ($nsNameToSchemaLocations[$i][$nsName] == $url) {
                    break;
                }
            }

            if (!isset($valueTypeXNamePairMaps[$i])) {
                $valueTypeXNamePairMaps[$i] = [];
            }

            $valueTypeXNamePairMaps[$i][] =
                [ $value, $xsd[$typeId]->getComponentXName() ];
        }

        $result = [];

        for ($i = 0; $i < count($valueTypeXNamePairMaps); $i++) {
            $result = array_merge(
                $result,
                self::validateAux(
                    $valueTypeXNamePairMaps[$i],
                    self::createXsdText($nsNameToSchemaLocations[$i])
                )
            );
        }

        return $result;
    }
}
