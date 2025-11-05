<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\Schema;
use alcamo\xml\XName;

/**
 * @brief XML Schema component predefined in the XML Schema specification
 *
 * @date Last reviewed 2021-07-09
 */
abstract class AbstractPredefinedComponent extends AbstractComponent
{
    private $xName_; ///< XName

    public function __construct(Schema $schema, XName $xName)
    {
        parent::__construct($schema);
        $this->xName_ = $xName;
    }

    /** @copydoc
     *  alcamo::dom::schema::component::ComponentInterface::getXName() */
    public function getXName(): XName
    {
        return $this->xName_;
    }
}
