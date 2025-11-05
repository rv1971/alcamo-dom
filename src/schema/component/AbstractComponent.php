<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\Schema;

/**
 * @brief XML Schema component
 *
 * @date Last reviewed 2025-11-05
 */
abstract class AbstractComponent implements ComponentInterface
{
    protected $schema_; ///< Schema

    public function __construct(Schema $schema)
    {
        $this->schema_ = $schema;
    }

    /** @copydoc
     *  alcamo::dom::schema::component::ComponentInterface::getSchema() */
    public function getSchema(): Schema
    {
        return $this->schema_;
    }
}
