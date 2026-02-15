<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\{Schema, SchemaFactory};
use alcamo\uri\FileUriFactory;
use alcamo\xml\XName;
use PHPUnit\Framework\TestCase;

class PredefinedAnySimpleTypeTest extends TestCase
{
    public const XSD_DIR = __DIR__ . DIRECTORY_SEPARATOR
        . '..' . DIRECTORY_SEPARATOR
        . '..' . DIRECTORY_SEPARATOR
        . '..' . DIRECTORY_SEPARATOR
        . 'xsd' . DIRECTORY_SEPARATOR;

    /* This also tests class AbstractComponent and class
     * AbstractPredefinedComponent. */
    public function testProps(): void
    {
        $schema = (new SchemaFactory())->getMainSchema();

        $type = $schema->getAnySimpleType();

        $this->assertSame($schema, $type->getSchema());

        $this->assertEquals(
            new XName(Schema::XSD_NS, 'anySimpleType'),
            $type->getXName()
        );

        $baseType = $type->getBaseType();

        $this->assertEquals(
            new XName(Schema::XSD_NS, 'anyType'),
            $baseType->getXName()
        );

        $this->assertTrue(
            $type->isEqualToOrDerivedFrom(Schema::XSD_NS . ' anySimpleType')
        );

        $this->assertTrue(
            $type->isEqualToOrDerivedFrom(Schema::XSD_NS . ' anyType')
        );

        $this->assertFalse(
            $type->isEqualToOrDerivedFrom(Schema::XSD_NS . ' string')
        );

        $this->assertNull($type->getFacet('length'));
        $this->assertNull($type->getHfpPropValue('bounded'));

        $this->assertFalse($type->isIntegral());
        $this->assertFalse($type->isNumeric());
    }
}
