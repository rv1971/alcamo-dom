<?php

namespace alcamo\dom\extended;

/**
 * @brief Provide a node registry
 *
 * @sa alcamo::dom::extended::RegisteredNodeTrait for an explanation of
 * the purpose of this registry.
 *
 * @date Last reviewed 2025-11-05
 */
trait NodeRegistryTrait
{
    private $nodeRegistry_ = [];

    /**
     * @brief Add $node to the registry
     *
     * @return The hash used as a key in the registry.
     *
     * The method does not tell whether the node was already registered
     * because this makes no difference.
     */
    public function register(\DOMNode $node): string
    {
        $hash = spl_object_hash($node);

        $this->nodeRegistry_[$hash] = $node;

        return $hash;
    }

    /// Needed in alcamo::dom::extended::Document::clearCache()
    private function clearNodeRegistry()
    {
        $this->nodeRegistry_ = [];
    }
}
