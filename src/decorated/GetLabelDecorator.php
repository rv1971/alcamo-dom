<?php

namespace alcamo\dom\decorated;

use alcamo\dom\GetLabelInterface;

/// Decorator providing getLabel()
class GetLabelDecorator extends AbstractDecorator implements GetLabelInterface
{
    use GetLabelTrait;
}
