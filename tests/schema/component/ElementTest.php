<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\{Schema, SchemaFactory};
use alcamo\xml\XName;
use PHPUnit\Framework\TestCase;

class ElementTest extends TestCase
{
    public function testProps(): void
    {
        $schema = (new SchemaFactory())->getMainSchema();

        $annotatedType = $schema->getGlobalType(Schema::XSD_NS . ' annotated');

        $annotationRef = new Element(
            $schema,
            $annotatedType->getXsdElement()->query(
                'xsd:complexContent/xsd:extension/xsd:sequence/xsd:element'
            )[0]
        );

        $this->assertEquals(
            new XName(Schema::XSD_NS, 'openAttrs'),
            $annotationRef->getType()->getBaseType()->getXName()
        );

        $typeDefParticle =
            $schema->getGlobalGroup(Schema::XSD_NS . ' typeDefParticle');

        $this->assertEquals(
            new XName(Schema::XSD_NS, 'groupRef'),
            (new Element(
                $schema,
                $typeDefParticle->getXsdElement()
                    ->query('xsd:choice/xsd:element')[0]
            ))
                ->getType()->getXName()
        );
    }
}
