<?php

namespace alcamo\dom;

use Psr\Http\Message\UriInterface;

/**
 * @brief Class featuring base URI and resolution relative to it
 *
 * @date Last reviewed 2025-09-15
 */
interface BaseUriInterface
{
    public function getBaseUri(): ?UriInterface;

    /// Resolve $uri relative to base URI, if possible
    public function resolveUri($uri): ?UriInterface;
}
