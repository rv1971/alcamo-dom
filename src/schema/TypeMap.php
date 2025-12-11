<?php

namespace alcamo\dom\schema;

use alcamo\dom\schema\component\TypeInterface;
use alcamo\exception\Locked;

/**
 * @brief Map assigning any kind of data to (base) types
 *
 * The lookup() method searches for base types if the type itself has no
 * mapping. The result of the lookup is cached in the map to speed up further
 * lookups of the same type.
 *
 * @date Last reviewed 2025-11-07
 */
class TypeMap
{
    private $map_;          ///< Array with SPL hashes of type objects as keys
    private $defaultValue_; ///< Default value if no element is found
    private $isLocked_;     ///< Whether entries have been added to $map_

    /**
     * @param $map Map whose keys are type XName strings
     */
    public static function newFromSchemaAndXNameMap(
        Schema $schema,
        iterable $map,
        $defaultValue = null
    ): self {
        return new static(
            static::createHashMapFromSchemaAndXNameMap($schema, $map),
            $defaultValue
        );
    }

    /**
     * @brief Create map as needed by __construct()
     *
     * @param $map Map whose keys are XName strings
     *
     * @return Array with SPL hashes of type objects as keys
     */
    public static function createHashMapFromSchemaAndXNameMap(
        Schema $schema,
        iterable $map
    ): array {
        $hashMap = [];

        foreach ($map as $xNameString => $value) {
            /** @note Unknown XNames are silently ignored, so that a fixed map
             *  can be used for a number of schemas which might not have all
             *  types. */
            $type = $schema->getGlobalType($xNameString);

            if (isset($type)) {
                $hashMap[spl_object_hash($type)] = $value;
            }
        }

        return $hashMap;
    }

    /**
     * @param $map Array with SPL hashes of type objects as keys
     */
    public function __construct(array $map, $defaultValue = null)
    {
        $this->map_ = $map;
        $this->defaultValue_ = $defaultValue;
    }

    /// Array with SPL hashes of type objects as keys
    public function getMap(): array
    {
        return $this->map_;
    }

    public function getDefaultValue()
    {
        return $this->defaultValue_;
    }

    /**
     * @brief Add map items, not replacing existing ones
     *
     * @param $map Array with SPL hashes of type objects as keys
     */
    public function addItems(array $map)
    {
        if ($this->isLocked_) {
            /** @throw alcamo::exception::Locked when attempting to modify a
             *  map to which entries have already been added by lookup(). */
            throw new Locked();
        }

        $this->map_ = $this->map_ + $map;
    }

    /**
     * @brief Add map items, replacing existing ones
     *
     * @param $map Array with SPL hashes of type objects as keys
     */
    public function replaceItems(array $map)
    {
        if ($this->isLocked_) {
            /** @throw alcamo::exception::Locked when attempting to modify a
             *  map to which entries have already been added by lookup(). */
            throw new Locked();
        }

        $this->map_ = $map + $this->map_;
    }

    public function lookup(TypeInterface $type)
    {
        $hash = spl_object_hash($type);

        /** If $type appears in the map, return the value assigned to it. */

        /* First try isset() which is fast but does not find a value of
         * `null`. If not successfull, try the slower array_key_exists() which
         * also finds `null`. */
        if (isset($this->map_[$hash]) || array_key_exists($hash, $this->map_)) {
            return $this->map_[$hash];
        }

        /** Otherwise look for the first base type that appears in the map. If
         *  there is none, return the default value. */
        $result = $this->defaultValue_;

        $baseType = $type->getBaseType();

        if (isset($baseType)) {
            $result = $this->lookup($baseType);
        }

        /** Cache any new result in the map. */
        $this->map_[$hash] = $result;

        /** Once any new result has been added, the map must not be modified
         * any more because the entry that has been added might become
         * incorrect. */
        $this->isLocked_ = true;

        return $result;
    }
}
