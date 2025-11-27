<?php

namespace alcamo\dom\schema;

use alcamo\dom\{DocumentFactoryInterface, HavingDocumentFactoryInterface};
use alcamo\exception\Unsupported;
use alcamo\uri\Uri;
use alcamo\xml\XName;
use GuzzleHttp\Psr7\UriResolver;
use Psr\Http\Message\UriInterface;

/**
 * @brief Class that validates data of XSD simple types given by URIs
 *
 * Supported are URIs that follow the id solution in [XML Schema Datatypes in
 * RDF and OWL](https://www.w3.org/TR/swbp-xsch-datatypes), provided that each
 * referenced type has an ID attribute (either `id` which is declared to be ID
 * in the internal subset of the XSD, or `xml:id`) identical to the name of
 * the type.
 *
 * @date Last reviewed 2021-07-11
 */
class TypeUriBasedSimpleTypeValidator extends AbstractSimpleTypeValidator implements
    HavingDocumentFactoryInterface
{
    private $documentFactory_; ///< DocumentFactoryInterface

    /**
     * @param $baseUri string|UriInterface Base URI to locate XSDs
     */
    public static function newFromBaseUri($baseUri): self
    {
        $class = static::DEFAULT_DOCUMENT_FACTORY_CLASS;

        return new static(new $class($baseUri));
    }

    /**
     * @param $documentFactory Document factory to create XSDs from URIs
     */
    public function __construct(
        ?DocumentFactoryInterface $documentFactory = null
    ) {
        if (isset($documentFactory)) {
            $this->documentFactory_ = $documentFactory;
        } else {
            $class = static::DEFAULT_DOCUMENT_FACTORY_CLASS;

            $this->documentFactory_ = new $class();
        }
    }

    /// Get the document factory used to create XSDs from URIs
    public function getDocumentFactory(): DocumentFactoryInterface
    {
        return $this->documentFactory_;
    }

    /*
     * @brief Validate data
     *
     * @param $valueTypeUriPairs Pairs consisting of a value and the URI of a
     * type.
     *
     * @return Array mapping keys of items in $valueTypeXNamePairs to
     * (potentially multi-line) error messages. Empty array if no errors
     * occurred.
     */
    public function validate($valueTypeUriPairs): array
    {
        /* The input may contain references to two or more XSDs for the
         * same namespace none of which is included in the other. Since an XSD
         * cannot import more than one XSD with the same target namespace, the
         * following algorithm creates a number of in-memory XSDs as
         * needed. This makes it possible to do without intermediate XSDs in
         * disk files. */

        /* Numerically-indexed array of maps mapping namespace names to
         * schema locations. */
        $nsNameToSchemaLocationMaps = [];

        /* Numerically-indexed array of maps mapping values to type XNames. */
        $valueTypeXNamePairMaps = [];

        $baseUri = $this->documentFactory_->getBaseUri();

        foreach ($valueTypeUriPairs as $key => $pair) {
            [ $value, $typeUri ] = $pair;
            [ $schemaLocation, $typeId ] = explode('#', $typeUri, 2);

            if (strpos($typeId, '(') !== false) {
                /** @throw alcamo::exception::Unsupported when attempting to
                 *  use a type URI containing a pointer that is not an id. */
                throw (new Unsupported())->setMessageContext(
                    [ 'feature' => 'Non-id pointers to XSD types' ]
                );
            }

            $uri = UriResolver::resolve($baseUri, new Uri($schemaLocation));

            $nsName = TargetNsCache::getInstance()[$uri];

            /* Store the mapping of namespace name to schema location in a map
             * so that it does not conflict with other schema locations for
             * the same namespace. */
            for ($i = 0;; $i++) {
                /* Create a new map if none of those created so far is
                 * suitable. */
                if (!isset($nsNameToSchemaLocationMaps[$i])) {
                    $nsNameToSchemaLocationMaps[$i] = [ $nsName => $uri ];
                    break;
                }

                /* Add to the map if not yet present. */
                if (!isset($nsNameToSchemaLocationMaps[$i][$nsName])) {
                    $nsNameToSchemaLocationMaps[$i][$nsName] = $uri;
                    break;
                }

                /* Accept existing mapping if schema location is the desired
                 * one. */
                if ($nsNameToSchemaLocationMaps[$i][$nsName] == $uri) {
                    break;
                }

                /* If none of the above applies, try next map. */
            }

            /* Create a new map if needed. */
            if (!isset($valueTypeXNamePairMaps[$i])) {
                $valueTypeXNamePairMaps[$i] = [];
            }

            $valueTypeXNamePairMaps[$i][$key] =
                [ $value, new XName($nsName, $typeId) ];
        }

        $result = [];

        for ($i = 0; isset($valueTypeXNamePairMaps[$i]); $i++) {
            $result += $this->validateAux(
                $valueTypeXNamePairMaps[$i],
                $this->createXsdText($nsNameToSchemaLocationMaps[$i])
            );
        }

        return $result;
    }
}
