<?php

namespace alcamo\dom\psvi;

use alcamo\dom\decorated\HavingDocumentationDecorator
    as BaseHavingDocumentationDecorator;

/// Decorator providing getLabel()
class HavingDocumentationDecorator extends BaseHavingDocumentationDecorator
{
    use HavingDocumentationTrait;
}
