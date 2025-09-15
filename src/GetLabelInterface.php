<?php

namespace alcamo\dom;

/**
 * @brief Class featuring getLabel()
 *
 * @date Last reviewed 2025-09-15
 */
interface GetLabelInterface extends GetMetaDataInterface
{
    /**
     * @brief Get a human-readable label, if available
     *
     * @param $lang Desired language.
     *
     * @param $fallbackFlags OR-Combination of the constants in
     * GetMetaDataInterface: which fallbacks to try if a label in the desired
     * language is not available.
     */
    public function getLabel(
        ?string $lang = null,
        ?int $fallbackFlags = null
    ): ?string;
}
