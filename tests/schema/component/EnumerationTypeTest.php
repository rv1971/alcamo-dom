<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\Schema;
use PHPUnit\Framework\TestCase;

class EnumerationTypeTest extends TestCase
{
    public function test(): void
    {
        $schema = Schema::getBuiltinSchema();

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
