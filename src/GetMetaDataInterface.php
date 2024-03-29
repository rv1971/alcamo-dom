<?php

namespace alcamo\dom;

/// Common constants for GetLabelInterface and GetCommentInterface
interface GetMetaDataInterface
{
    /// Fallback to another language if the requested one is not available
    public const FALLBACK_TO_OTHER_LANG = 1;

    /// Fallback to the name of the node's type, if any
    public const FALLBACK_TO_TYPE_NAME  = 2;

    /// Fallback to the name of the node, if any
    public const FALLBACK_TO_NAME       = 4;
}
