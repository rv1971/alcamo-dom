<?php

namespace alcamo\dom\extended;

trait NodeRegistryTrait
{
    private $nodeRegistry_ = [];

    /**
     * Registering a node here ensures conservation of any data members
     * attached to the node in derived classes. Without this, such attached
     * data would be destroyed when the node is not referenced any more.
     */
    public function register(\DOMNode $node, string $hash)
    {
        $this->nodeRegistry_[$hash] = $node;
    }
}
