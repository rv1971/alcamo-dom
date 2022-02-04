<?php

namespace alcamo\dom;

/// Interface for getLabel()
interface GetLabelInterface
{
    /// Fallback to another language if the requested one is not available
    public const FALLBACK_TO_OTHER_LANG = 1;

    /// Fallback to the name of the node's type, if any
    public const FALLBACK_TO_TYPE_NAME  = 2;

    /// Fallback to the name of the node, if any
    public const FALLBACK_TO_NAME       = 4;

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
