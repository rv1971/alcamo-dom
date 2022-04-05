<?php

namespace alcamo\dom\psvi;

use alcamo\dom\GetLabelInterface;
use alcamo\dom\decorated\AbstractDecorator;

/// Decorator providing getLabel()
class GetLabelDecorator extends AbstractDecorator implements GetLabelInterface
{
    use GetLabelTrait;
}
