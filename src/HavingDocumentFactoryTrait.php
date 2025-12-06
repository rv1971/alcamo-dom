<?php

namespace alcamo\dom;

trait HavingDocumentFactoryTrait
{
    private $documentFactory_;       ///< DocumentFactoryInterface

    public function getDocumentFactory(): DocumentFactoryInterface
    {
        return $this->documentFactory_;
    }
}
