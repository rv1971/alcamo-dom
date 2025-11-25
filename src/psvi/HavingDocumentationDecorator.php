<?php

namespace alcamo\dom\psvi;

use alcamo\dom\decorated\HavingDocumentationDecorator
    as BaseHavingDocumentationDecorator;

/**
 * @brief Decorator providing documentation methods
 *
 * @date Last reviewed 2025-11-25
 */
class HavingDocumentationDecorator extends BaseHavingDocumentationDecorator
{
    use HavingDocumentationTrait;
}
