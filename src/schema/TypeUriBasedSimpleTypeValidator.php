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

    public static function newFromBaseUrl($baseUrl): self
    {
        $class = static::DEFAULT_DOCUMENT_FACTORY_CLASS;

        return new static(new $class($baseUrl));
    }

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

    public function getDocumentFactory(): DocumentFactoryInterface
    {
        return $this->documentFactory_;
    }

    /*
     * @param $valueTypeUriPairs Pairs consisting of a value and the URI of a
     * type.
     */
    public function validate($valueTypeUriPairs): array
    {
        /* The input may contain references to two or more XSDs for the
         * same namespace none of which is included in the other. Since an XSD
         * cannot import more than one XSD with the same target namespace, the
         * following algorithm creates additional in-memory XSDs as needed. This
         * makes it possible to do without intermediate XSDs in disk files. */

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

            $url = UriResolver::resolve(
                $this->documentFactory_->getBaseUri(),
                new Uri($schemaLocation)
            );

            $nsName = TargetNsCache::getInstance()[$url];

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
                [ $value, new XName($nsName, $typeId) ];
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
