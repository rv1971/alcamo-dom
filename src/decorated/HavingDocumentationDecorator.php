<?php

namespace alcamo\dom\decorated;

use alcamo\dom\HavingDocumentationInterface;

/**
 * @brief Decorator providing documentation methods
 *
 * @date Last reviewed 2025-10-23
 */
class HavingDocumentationDecorator extends AbstractElementDecorator implements
    HavingDocumentationInterface
{
    use HavingDocumentationTrait;

    /// Relative XPath to <rdfs:label> elements
    protected const RDFS_LABEL_XPATH = 'rdfs:label';

    /// Relative XPath to <rdfs:comment> elements
    protected const RDFS_COMMENT_XPATH = 'rdfs:comment';
}
