<?php

namespace alcamo\dom;

use Psr\Http\Message\UriInterface;

/**
 * @brief Class featuring base URI and resolution relative to it
 *
 * @date Last reviewed 2025-10-23
 */
interface HavingBaseUriInterface
{
    public function getBaseUri(): ?UriInterface;

    /// Resolve $uri relative to base URI, if possible
    public function resolveUri($uri): ?UriInterface;
}
