<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\Schema;
use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class EnumerationUnionTypeTest extends TestCase
{
    public const FOO_NS = 'http://foo.example.org';

    public function test(): void
    {
        $fooUri = (new FileUriFactory())
            ->create(__DIR__ . DIRECTORY_SEPARATOR . 'foo.xsd');

        /* Contains XMLSchema.xsd as built-in. */
        $schema = Schema::newFromUris([ $fooUri ]);

        $type =
            $schema->getGlobalType(self::FOO_NS . ' UnionType');

        $expectedEnumerators = [
            'qualified',
            'unqualified',
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
