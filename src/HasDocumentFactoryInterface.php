<?php

namespace alcamo\dom;

/**
 * @brief Class that provides a document factory
 *
 * @date Last reviewed 2021-06-30
 */
interface HasDocumentFactoryInterface
{
    public function getDocumentFactory(): DocumentFactoryInterface;
}
