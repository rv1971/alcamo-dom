<?php

namespace alcamo\dom\decorated;

use alcamo\dom\GetCommentInterface;

/// Decorator providing getLabel()
class GetLabelAndCommentDecorator extends GetLabelDecorator implements
    GetCommentInterface
{
    use GetCommentTrait;

    /// Relative XPath to <rdfs:comment> elements
    protected const RDFS_COMMENT_XPATH = 'rdfs:comment';
}
