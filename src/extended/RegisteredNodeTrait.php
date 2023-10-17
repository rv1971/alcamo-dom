<?php

namespace alcamo\dom\extended;

/**
 * @brief Provide a register() method
 *
 * When a derived class for a node adds properties, these properties are lost
 * when the node is not referenced by a PHP variable any more. When accessing
 * the node again via other means (e.g. via XPath or as a child or sibling of
 * another node), a new instance of the derived class is constructed.
 *
 * To avoid this, NodeRegistryTrait provides a node registry for use in a
 * derived document class, which is simply an array of node
 * objects. RegisteredNodeTrait provides a register() method to add the
 * present node to that registry. Thus, any additional properties are
 * conserved.
 *
 * Since the registry is a property of the derived document class, its
 * lifetime is that of the document class object. In certain circumstances,
 * alcamo::dom::Document::conserve() must be used to prevent the derived
 * document from being destroyed.
 *
 * @date Last reviewed 2021-07-01
 */
trait RegisteredNodeTrait
{
    private $hash_;  ///< string

    /**
     * @brief Compute a hash and call NodeRegistryTrait::register()
     *
     * When calling this method a second time, the cached hash is returned.
     */
    public function register(): string
    {
        if (!isset($this->hash_)) {
            $this->ownerDocument->register(
                $this,
                ($this->hash_ = spl_object_hash($this))
            );
        }

        return $this->hash_;
    }
}
