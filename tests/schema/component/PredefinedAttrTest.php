<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\Schema;
use alcamo\xml\XName;
use PHPUnit\Framework\TestCase;

class PredefinedAttrTest extends TestCase
{
    /* This also tests class AbstractComponent and class
     * AbstractPredefinedComponent. */
    public function testProps(): void
    {
        /* Contains XMLSchema.xsd as built-in. */
        $schema = Schema::newFromUris([]);

        $xsiNilAttr = $schema->getGlobalAttr(Schema::XSI_NS . ' nil');

        $this->assertEquals(
            new XName(Schema::XSD_NS, 'boolean'),
            $xsiNilAttr->getType()->getXName()
        );

        $xsiTypeAttr = $schema->getGlobalAttr(Schema::XSI_NS . ' type');

        $this->assertEquals(
            new XName(Schema::XSD_NS, 'QName'),
            $xsiTypeAttr->getType()->getXName()
        );
    }
}
