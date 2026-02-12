<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\{Schema, SchemaFactory};
use alcamo\rdfa\{
    BooleanLiteral,
    IntegerLiteral,
    Node,
    RdfaData
};
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
        $booleanTrue = new BooleanLiteral(true);

        $int6Literal = new IntegerLiteral(6, Schema::XSD_NS . '#byte');

        $int5Literal = new IntegerLiteral(5, Schema::XSD_NS . '#byte');

        /* To fill Uri::composedComponents */
        (string)$booleanTrue->getDatatypeUri();
        (string)$int6Literal->getDatatypeUri();
        (string)$int5Literal->getDatatypeUri();

        $fooUri = (new FileUriFactory())
            ->create(__DIR__ . DIRECTORY_SEPARATOR . 'foo.xsd');

        /* Contains XMLSchema.xsd as built-in. */
        $schema = (new SchemaFactory())->createFromUris([ $fooUri ]);

        $fooInt =
            $schema->getGlobalElement(self::FOO_NS . ' foo-int')->getType();

        $fooUnsigned5 = $fooInt->getBaseType();

        $fooUnsigned6 = $fooUnsigned5->getBaseType();

        $this->assertEquals(
            RdfaData::newFromIterable(
                [
                    [ self::BAR_NS . 'isLimitedInt', $booleanTrue ],
                    [ self::BAR_NS . 'bits', $int6Literal ],
                    [
                        Schema::DC_NS . 'seeAlso',
                        new Node('http://foo.example.org/documentation/FooUnsigned6')
                    ]
                ],
                null,
                RdfaData::URI_AS_KEY
            ),
            $fooUnsigned6->getRdfaData()
        );

        $this->assertEquals(
            RdfaData::newFromIterable(
                [
                    [ self::BAR_NS . 'isLimitedInt', $booleanTrue ],
                    [ self::BAR_NS . 'bits', $int5Literal ],
                    [
                        Schema::DC_NS . 'seeAlso',
                        new Node('http://foo.example.org/documentation/FooUnsigned5')
                    ],
                ],
                null,
                RdfaData::URI_AS_KEY
            ),
            $fooUnsigned5->getRdfaData()
        );

        $this->assertEquals(
            RdfaData::newFromIterable(
                [
                    [ self::BAR_NS . 'isLimitedInt', $booleanTrue ]
                ],
                null,
                RdfaData::URI_AS_KEY
            ),
            $fooInt->getRdfaData()
        );
    }
}
