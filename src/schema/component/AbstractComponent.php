<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\Schema;

abstract class AbstractComponent implements ComponentInterface
{
    protected $schema_; ///< Schema

    public function __construct(Schema $schema)
    {
        $this->schema_ = $schema;
    }

    public function getSchema(): Schema
    {
        return $this->schema_;
    }
}
