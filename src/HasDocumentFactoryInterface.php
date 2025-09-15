<?php

namespace alcamo\dom;

/**
 * @brief Class that provides a document factory
 *
 * @date Last reviewed 2025-09-15
 */
interface HasDocumentFactoryInterface
{
    public function getDocumentFactory(): DocumentFactoryInterface;
}
