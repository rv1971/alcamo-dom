<?php

namespace alcamo\dom\psvi;

use alcamo\dom\{GetCommentInterface, GetLabelInterface};
use alcamo\dom\decorated\{
    AbstractDecorator,
    GetCommentTrait,
    GetLabelAndCommentDecorator as BaseGetLabelAndCommentDecorator
};

/// Decorator providing getLabel()
class GetLabelAndCommentDecorator extends BaseGetLabelAndCommentDecorator implements
    GetCommentInterface,
    GetLabelInterface
{
    use GetLabelTrait;
    use GetCommentTrait;
}
