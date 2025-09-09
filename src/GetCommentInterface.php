<?php

namespace alcamo\dom;

/// Interface for getComment()
interface GetCommentInterface extends GetMetaDataInterface
{
    /**
     * @brief Get a human-readable comment, if present
     *
     * @param $lang Desired language.
     *
     * @param $fallbackFlags OR-Combination of the above constants: which
     * fallbacks to try if a comment in the desired language is not available.
     */
    public function getComment(
        ?string $lang = null,
        ?int $fallbackFlags = null
    ): ?string;
}
