<?php

namespace alcamo\dom;

use alcamo\collection\ReadonlyCollection;
use alcamo\exception\InvalidType;
use alcamo\uri\Uri;

/**
 * @brief Array of DOM documents indexed by their dc:identifier
 *
 * Uses DocumentFactory, hence features caching as well as automatic
 * determination of the document classes.
 *
 * @date Last reviewed 2021-07-01
 */
class Documents extends ReadonlyCollection
{
    /**
     * @brief Construct from a collection of documents
     *
     * @param $docs Iterable whose values are Document objects.
     */
    public function __construct(iterable $docs)
    {
        $docs2 = [];

        foreach ($docs as $key => $doc) {
            if (!($doc instanceof Document)) {
                /** @throw alcamo::exception::InvalidType when item value is
                 *  not a document. */
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
                /*  Otherwise use the file name. */
                $key = basename((new Uri($doc->documentURI))->getPath());
            }

            $docs2[$key] = $doc;
        }

        parent::__construct($docs2);
    }
}
