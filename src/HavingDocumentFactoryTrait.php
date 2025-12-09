<?php

namespace alcamo\dom;

/**
 * @brief Simple implementation of HavingDocumentFactoryInterface
 *
 * @date Last reviewed 2025-12-09
 */
trait HavingDocumentFactoryTrait
{
    private $documentFactory_; ///< DocumentFactoryInterface

    /**
     * @param $documentFactory Factory used to create documents.
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

    public function getDocumentFactory(): DocumentFactoryInterface
    {
        return $this->documentFactory_;
    }
}
