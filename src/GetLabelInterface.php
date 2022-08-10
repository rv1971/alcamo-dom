<?php

namespace alcamo\dom;

/// Interface for getLabel()
interface GetLabelInterface extends GetMetaDataInterface
{
    /**
     * @brief Get an `rdfs:label` attribute or element, if present
     *
     * @param $lang Desired language.
     *
     * @param $fallbackFlags OR-Combination of the above constants: which
     * fallbacks to try if a label in the desired language is not available.
     */
    public function getLabel(
        ?string $lang = null,
        ?int $fallbackFlags = null
    ): ?string;
}
