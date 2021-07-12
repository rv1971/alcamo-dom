<?php

namespace alcamo\dom;

use GuzzleHttp\Psr7\{Uri, UriResolver};
use Psr\Http\Message\UriInterface;

/// Implementation of BaseUriInterface for DOM nodes
trait BaseUriTrait
{
    public function getBaseUri(): ?UriInterface
    {
        return isset($this->baseURI) ? new Uri($this->baseURI) : null;
    }

    /// @copybrief BaseUriInterface::resolveUri()
    public function resolveUri($uri): ?UriInterface
    {
        if (!($uri instanceof UriInterface)) {
            $uri = new Uri($uri);
        }

        if ($uri->getScheme() !== '') {
            return $uri;
        }

        return isset($this->baseURI)
            ? UriResolver::resolve(new Uri($this->baseURI), $uri)
            : null;
    }
}
