<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\{Schema, SchemaFactory};
use PHPUnit\Framework\TestCase;

class EnumerationTypeTest extends TestCase
{
    public function test(): void
    {
        $schema = (new SchemaFactory())->getMainSchema();

        $type =
            $schema->getGlobalType(Schema::XSD_NS . ' typeDerivationControl');

        $expectedEnumerators = [
            'extension',
            'restriction',
            'list',
            'union'
        ];

        $this->assertSame(
            $expectedEnumerators,
            array_keys($type->getEnumerators())
        );

        foreach ($type->getEnumerators() as $value => $node) {
            $this->assertSame($value, (string)$node);
        }
    }
}
