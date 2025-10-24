<?php

namespace alcamo\dom;

use GuzzleHttp\Psr7\{Uri, UriResolver};
use Psr\Http\Message\UriInterface;

/**
 * @brief Implementation of HavingBaseUriInterface for DOM nodes
 *
 * @date Last reviewed 2025-10-23
 */
trait HavingBaseUriTrait
{
    /** @copydoc alcamo::dom::HavingBaseUriInterface::getBaseUri() */
    public function getBaseUri(): ?UriInterface
    {
        return isset($this->baseURI) ? new Uri($this->baseURI) : null;
    }

    /** @copydoc alcamo::dom::HavingBaseUriInterface::resolveUri() */
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
