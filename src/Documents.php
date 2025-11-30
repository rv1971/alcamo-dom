<?php

namespace alcamo\dom;

use alcamo\collection\ReadonlyCollection;
use alcamo\exception\{DataValidationFailed, InvalidType};
use alcamo\uri\Uri;

/**
 * @brief Collection of DOM documents indexed by their identifier
 *
 * Uses alcamo::dom::DocumentFactory, hence features caching as well as
 * automatic determination of the document classes.
 *
 * @date Last reviewed 2021-07-01
 */
class Documents extends ReadonlyCollection
{
    /**
     * @brief Construct from a collection of documents
     *
     * @param $docs Iterable whose values are alcamo::dom::Document objects.
     */
    public function __construct(iterable $docs)
    {
        $docs2 = [];

        foreach ($docs as $key => $doc) {
            if (!($doc instanceof Document)) {
                /** @throw alcamo::exception::InvalidType when item value is
                 *  not a alcamo::dom::Document object. */
                throw (new InvalidType())->setMessageContext(
                    [
                        'value' => $doc,
                        'expectedOneOf' => [ Document::class ],
                        'forKey' => $key
                    ]
                );
            }

            /** If a key in $docs is a string, use it for the key in the
             * result collection. */

            if (!is_string($key)) {
                /** Otherwise, if the document element has a `dc:identifier`
                 * attribute, use it. */

                $key = $doc->documentElement->getAttributeNS(
                    Document::DC_NS,
                    'identifier'
                );
            }

            if ($key == '') {
                /*  Otherwise use the document URI path. */
                $key = basename((new Uri($doc->documentURI))->getPath());
            }

            if (isset($docs2[$key]) && $docs2[$key] !== $doc) {
                /** @throw alcamo::exception::DataValidationFailed when a key
                 *  appears twice for two difefrent documents. */
                throw (new DataValidationFailed())->setMessageContext(
                    [
                        'value' => $doc,
                        'forKey' => $key,
                        'extraMessage' =>
                            'two different documents for the same key'
                    ]
                );
            }

            $docs2[$key] = $doc;
        }

        parent::__construct($docs2);
    }
}
