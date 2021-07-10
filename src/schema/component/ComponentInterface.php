<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\Schema;
use alcamo\xml\XName;

/**
 * @namespace alcamo::dom::schema::component
 *
 * @brief Classes modelling XML %Schema components
 */

/**
 * @brief XML %Schema component
 *
 * @date Last reviewed 2021-07-09
 */
interface ComponentInterface
{
    /// Schema the component belongs to
    public function getSchema(): Schema;

    /// Extended name of component, if any
    public function getXName(): ?XName;
}
