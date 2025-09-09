<?php

namespace alcamo\dom\decorated;

use alcamo\dom\GetLabelInterface;

/// Decorator providing getLabel()
class GetLabelDecorator extends AbstractDecorator implements GetLabelInterface
{
    use GetLabelTrait;

    /// Relative XPath to <rdfs:label> elements
    protected const RDFS_LABEL_XPATH = 'rdfs:label';
}
