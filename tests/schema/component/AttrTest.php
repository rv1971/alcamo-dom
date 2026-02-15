<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\{Schema, SchemaFactory};
use alcamo\xml\XName;
use PHPUnit\Framework\TestCase;

class AttrTest extends TestCase
{
    public function testProps(): void
    {
        $schema = (new SchemaFactory())->getMainSchema();

        $schemaElementType =
            $schema->getGlobalElement(Schema::XSD_NS . ' schema')->getType();

        $targetNamespace = $schemaElementType->getAttrs()['targetNamespace'];

        $this->assertEquals(
            new XName(Schema::XSD_NS, 'anyURI'),
            $targetNamespace->getType()->getXName()
        );

        $langRef = $schemaElementType->getAttrs()[Schema::XML_NS . ' lang'];

        $this->assertSame(
            2,
            count($langRef->getRefAttr()->getType()->getMemberTypes())
        );

        $this->assertSame(
            $langRef->getRefAttr()->getType(),
            $langRef->getType()
        );
    }
}
