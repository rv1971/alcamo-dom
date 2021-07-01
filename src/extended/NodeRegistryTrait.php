<?php

namespace alcamo\dom\extended;

/**
 * @brief Provide a node registry
 *
 * @sa See RegisteredNodeTrait for an explanation of the purpose of this
 * registry.
 *
 * @date Last reviewed 2021-07-01
 */
trait NodeRegistryTrait
{
    private $nodeRegistry_ = [];

    /// Add $node to the registry, using $hash as its key
    public function register(\DOMNode $node, string $hash): void
    {
        $this->nodeRegistry_[$hash] = $node;
    }
}
