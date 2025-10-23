<?php

namespace alcamo\dom\decorated;

use alcamo\dom\HavingDocumentationInterface;

/// Decorator providing getLabel()
class HavingDocumentationDecorator extends AbstractDecorator implements
    HavingDocumentationInterface
{
    use HavingDocumentationTrait;

    /// Relative XPath to <rdfs:label> elements
    protected const RDFS_LABEL_XPATH = 'rdfs:label';

    /// Relative XPath to <rdfs:comment> elements
    protected const RDFS_COMMENT_XPATH = 'rdfs:comment';
}
