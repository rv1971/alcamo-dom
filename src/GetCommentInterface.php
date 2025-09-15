<?php

namespace alcamo\dom;

/**
 * @brief Class featuring getComment()
 *
 * @date Last reviewed 2025-09-15
 */
interface GetCommentInterface extends GetMetaDataInterface
{
    /**
     * @brief Get a human-readable comment, if present
     *
     * @param $lang Desired language.
     *
     * @param $fallbackFlags OR-Combination of the constants in
     * GetMetaDataInterface: which fallbacks to try if a comment in the
     * desired language is not available.
     */
    public function getComment(
        ?string $lang = null,
        ?int $fallbackFlags = null
    ): ?string;
}
