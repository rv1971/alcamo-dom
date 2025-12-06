<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\{Schema, SchemaFactory};
use alcamo\uri\FileUriFactory;
use alcamo\xml\XName;
use PHPUnit\Framework\TestCase;

class AtomicTypeTest extends TestCase
{
    public const FOO_NS = 'http://foo.example.org';

    public const BAR_NS = 'https://bar.example.com#';

    /**
     * @dataProvider isEqualToOrDerivedFromProvider
     */
    public function testIsEqualToOrDerivedFrom(
        $type,
        $baseType,
        $expected
    ): void {
        $this->assertSame($expected, $type->isEqualToOrDerivedFrom($baseType));
    }

    public function isEqualToOrDerivedFromProvider(): array
    {
        $fooUri = (new FileUriFactory())
            ->create(__DIR__ . DIRECTORY_SEPARATOR . 'foo.xsd');

        /* Contains XMLSchema.xsd as built-in. */
        $schema = (new SchemaFactory())->createFromUris([ $fooUri ]);

        return [
            [
                $schema->getGlobalType(Schema::XSD_NS . ' boolean'),
                Schema::XSD_NS . ' boolean',
                true
            ],
            [
                $schema->getGlobalType(Schema::XSD_NS . ' int'),
                Schema::XSD_NS . ' long',
                true
            ],
            [
                $schema->getGlobalType(Schema::XSD_NS . ' byte'),
                Schema::XSD_NS . ' integer',
                true
            ],
            [
                $schema->getGlobalType(Schema::XSD_NS . ' float'),
                Schema::XSD_NS . ' decimal',
                false
            ],
            [
                $schema->getGlobalType(Schema::XSD_NS . ' double'),
                Schema::XSD_NS . ' decimal',
                false
            ],
            [
                $schema->getGlobalType(Schema::XSD_NS . ' float'),
                Schema::XSD_NS . ' anySimpleType',
                true
            ],
            [
                $schema->getGlobalElement(self::FOO_NS . ' foo-int')->getType(),
                Schema::XSD_NS . ' byte',
                true
            ]
        ];
    }

    /**
     * @dataProvider propsProvider
     */
    public function testProps(
        $type,
        $isNumeric,
        $isIntegral,
        $baseTypeXName
    ): void {
        $this->assertSame($isNumeric, $type->isNumeric());
        $this->assertSame($isIntegral, $type->isIntegral());

        $this->assertEquals(
            $baseTypeXName,
            $type->getBaseType()->getXName()
        );
    }

    public function propsProvider(): array
    {
        $fooUri = (new FileUriFactory())
            ->create(__DIR__ . DIRECTORY_SEPARATOR . 'foo.xsd');

        /* Contains XMLSchema.xsd as built-in. */
        $schema = (new SchemaFactory())->createFromUris([ $fooUri ]);

        return [
            [
                $schema->getGlobalType(Schema::XSD_NS . ' string'),
                false,
                false,
                new XName(Schema::XSD_NS, 'anySimpleType')
            ],
            [
                $schema->getGlobalType(Schema::XSD_NS . ' decimal'),
                true,
                false,
                new XName(Schema::XSD_NS, 'anySimpleType')
            ],
            [
                $schema->getGlobalType(Schema::XSD_NS . ' float'),
                true,
                false,
                new XName(Schema::XSD_NS, 'anySimpleType')
            ],
            [
                $schema->getGlobalType(Schema::XSD_NS . ' integer'),
                true,
                true,
                new XName(Schema::XSD_NS, 'decimal')
            ],
            [
                $schema->getGlobalType(Schema::XSD_NS . ' short'),
                true,
                true,
                new XName(Schema::XSD_NS, 'int')
            ],
            [
                $schema->getGlobalElement(self::FOO_NS . ' foo-int')->getType(),
                true,
                true,
                new XName(self::FOO_NS, 'FooUnsigned5')
            ]
        ];
    }

    public function testAppinfo()
    {
        $fooUri = (new FileUriFactory())
            ->create(__DIR__ . DIRECTORY_SEPARATOR . 'foo.xsd');

        /* Contains XMLSchema.xsd as built-in. */
        $schema = (new SchemaFactory())->createFromUris([ $fooUri ]);

        $fooIntType =
            $schema->getGlobalElement(self::FOO_NS . ' foo-int')->getType();

        $this->assertNull(
            $fooIntType->getAppinfoMeta(self::BAR_NS . 'bits')
        );

        $this->assertNull(
            $fooIntType->getAppinfoLink(Schema::DC_NS . 'seeAlso')
        );

        $fooUnsigned5 = $fooIntType->getBaseType();

        $this->assertSame(
            5,
            $fooUnsigned5->getAppinfoMeta(self::BAR_NS . 'bits')->content
        );

        $this->assertSame(
            'http://foo.example.org/documentation/FooUnsigned5',
            $fooUnsigned5->getAppinfoLink(Schema::DC_NS . 'seeAlso')->href
        );

        $fooUnsigned6 = $fooUnsigned5->getBaseType();

        $this->assertSame(
            6,
            $fooUnsigned6->getAppinfoMeta(self::BAR_NS . 'bits')->content
        );

        $this->assertSame(
            'http://foo.example.org/documentation/FooUnsigned6',
            $fooUnsigned6->getAppinfoLink(Schema::DC_NS . 'seeAlso')->href
        );
    }
}
