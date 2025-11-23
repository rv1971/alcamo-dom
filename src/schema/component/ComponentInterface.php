<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\NamespaceConstantsInterface;
use alcamo\dom\schema\Schema;
use alcamo\xml\XName;

/**
 * @namespace alcamo::dom::schema::component
 *
 * @brief Classes modelling XML Schema components
 */

/**
 * @brief XML Schema component
 *
 * @date Last reviewed 2025-11-05
 */
interface ComponentInterface extends NamespaceConstantsInterface
{
    /// Get schema the component belongs to
    public function getSchema(): Schema;

    /// Get extended name of component, if any
    public function getXName(): ?XName;
}
