<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\{Schema, SchemaFactory};
use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class ListTypeTest extends TestCase
{
    public const FOO_NS = 'http://foo.example.org';

    public function test(): void
    {
        $schema = (new SchemaFactory())->getMainSchema();

        $type = $schema->getGlobalType(Schema::XSD_NS . ' IDREFS');

        $this->assertSame(
            $schema->getGlobalType(Schema::XSD_NS . ' IDREF'),
            $type->getItemType()
        );

        $this->assertNull($type->getHfpPropValue('bounded'));

        $this->assertFalse($type->isIntegral());
        $this->assertFalse($type->isNumeric());
    }

    /**
     * @dataProvider isPrintableProvider
     */
    public function testIsPrintable($typeXName, $expectedPrintable): void
    {
        $fooUri = (new FileUriFactory())
            ->create(__DIR__ . DIRECTORY_SEPARATOR . 'foo.xsd');

        /* Contains XMLSchema.xsd as built-in. */
        $schema = (new SchemaFactory())->createFromUris([ $fooUri ]);

        $this->assertSame(
            $expectedPrintable,
            $schema->getGlobalType($typeXName)->isPrintable()
        );
    }

    public function isPrintableProvider(): array
    {
        return [
            [ Schema::XSD_NS . ' IDREFS', true ],
            [ self::FOO_NS . ' limitedHexBinaryList', false ]
        ];
    }
}
