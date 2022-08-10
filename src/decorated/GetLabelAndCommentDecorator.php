<?php

namespace alcamo\dom\decorated;

use alcamo\dom\GetCommentInterface;

/// Decorator providing getLabel()
class GetLabelAndCommentDecorator extends GetLabelDecorator implements
    GetCommentInterface
{
    use GetCommentTrait;
}
