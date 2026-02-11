<?php

namespace alcamo\dom;

/**
 * @brief Having human-readable documentation
 *
 * @date Last reviewed 2025-10-23
 */
interface HavingDocumentationInterface
{
    /// Fallback to another language if the requested one is not available
    public const FALLBACK_TO_OTHER_LANG = 1;

    /// Fallback to the fragment part of owl:sameAs, if any
    public const FALLBACK_TO_SAME_AS_FRAGMENT  = 2;

    /// Fallback to the name of the node's type, if any
    public const FALLBACK_TO_TYPE_NAME  = 4;

    /// Fallback to the name of the node, if any
    public const FALLBACK_TO_NAME       = 8;

    /**
     * @brief Get a human-readable label, if available
     *
     * @param $lang Desired language.
     *
     * @param $fallbackFlags OR-Combination of the constants in
     * alcamo::dom::HavingDocumentationInterface: which fallbacks to try if a
     * label in the desired language is not available.
     */
    public function getLabel(
        ?string $lang = null,
        ?int $fallbackFlags = null
    ): ?string;
}
