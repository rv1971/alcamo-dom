<?php

namespace alcamo\dom;

interface DocumentFactoryInterface
{
    public function createFromUrl(
        string $url,
        ?string $class = null,
        ?int $libXmlOptions = null
    ): Document;
}
