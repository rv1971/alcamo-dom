<?php

namespace alcamo\dom;

/**
 * @brief Class that provides a document factory
 *
 * @date Last reviewed 2025-10-23
 */
interface HavingDocumentFactoryInterface
{
    public function getDocumentFactory(): DocumentFactoryInterface;
}
