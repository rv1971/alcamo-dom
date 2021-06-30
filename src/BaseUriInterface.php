<?php

namespace alcamo\dom;

use Psr\Http\Message\UriInterface;

/// Object that may have a base URI
interface BaseUriInterface
{
    public function getBaseUri(): ?UriInterface;

    /// Resolve $uri relative to base URI, if possible
    public function resolve($uri): ?UriInterface;
}
