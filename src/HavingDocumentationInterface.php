<?php

namespace alcamo\dom;

use alcamo\rdfa\HavingLabelInterface;

/**
 * @brief Having human-readable documentation
 *
 * @date Last reviewed 2025-10-23
 */
interface HavingDocumentationInterface extends HavingLabelInterface
{
    /// Fallback to the fragment part of owl:sameAs, if any
    public const FALLBACK_TO_SAME_AS_FRAGMENT = 256;

    /// Fallback to the name of the node's type, if any
    public const FALLBACK_TO_TYPE_NAME        = 512;

    /// Fallback to the name of the node, if any
    public const FALLBACK_TO_NAME             = 1024;
}
