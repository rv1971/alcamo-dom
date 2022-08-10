<?php

namespace alcamo\dom\psvi;

use alcamo\dom\{GetCommentInterface, GetLabelInterface};
use alcamo\dom\decorated\{AbstractDecorator, GetCommentTrait};

/// Decorator providing getLabel()
class GetLabelAndCommentDecorator extends AbstractDecorator implements
    GetCommentInterface,
    GetLabelInterface
{
    use GetLabelTrait;
    use GetCommentTrait;
}
