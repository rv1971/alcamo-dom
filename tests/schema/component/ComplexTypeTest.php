<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\{Schema, SchemaFactory};
use alcamo\xml\XName;
use PHPUnit\Framework\TestCase;

class ComplexTypeTest extends TestCase
{
    public function testProps(): void
    {
        $schema = (new SchemaFactory())->getMainSchema();

        $attributeType = $schema->getGlobalType(Schema::XSD_NS . ' attribute');

        $this->assertEquals(
            new XName(Schema::XSD_NS, 'annotated'),
            $attributeType->getBaseType()->getXName()
        );

        $this->assertSame(
            [
                Schema::XSI_NS . ' type',
                'id',
                'name',
                'ref',
                'type',
                'use',
                'default',
                'fixed',
                'form'
            ],
            array_keys($attributeType->getAttrs())
        );

        $this->assertEquals(
            new XName(Schema::XSD_NS, 'ID'),
            $attributeType->getAttrs()['id']->getType()->getXName()
        );

        $schemaType =
            $schema->getGlobalElement(Schema::XSD_NS . ' schema')->getType();

        $this->assertEquals(
            new XName(Schema::XSD_NS, 'openAttrs'),
            $schemaType->getBaseType()->getXName()
        );

        $this->assertSame(
            [
                Schema::XSD_NS . ' simpleType',
                Schema::XSD_NS . ' complexType',
                Schema::XSD_NS . ' group',
                Schema::XSD_NS . ' attributeGroup',
                Schema::XSD_NS . ' element',
                Schema::XSD_NS . ' attribute',
                Schema::XSD_NS . ' notation',
                Schema::XSD_NS . ' annotation',
                Schema::XSD_NS . ' include',
                Schema::XSD_NS . ' import',
                Schema::XSD_NS . ' redefine'
            ],
            array_keys($schemaType->getElements())
        );

        $this->assertEquals(
            new XName(Schema::XSD_NS, 'openAttrs'),
            $attributeType->getElements()[Schema::XSD_NS . ' annotation']
                ->getType()
                ->getBaseType()
                ->getXName()
        );
    }
}
