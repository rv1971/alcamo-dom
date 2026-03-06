<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\{Schema, SchemaFactory};
use alcamo\rdfa\{
    BooleanLiteral,
    IntegerLiteral,
    LangStringLiteral,
    Node,
    RdfaData
};
use alcamo\uri\FileUriFactory;
use alcamo\xml\{NamespaceConstantsInterface, XName};
use PHPUnit\Framework\TestCase;

class AtomicTypeTest extends TestCase implements NamespaceConstantsInterface
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
                $schema->getGlobalType(self::XSD_NS . ' boolean'),
                self::XSD_NS . ' boolean',
                true
            ],
            [
                $schema->getGlobalType(self::XSD_NS . ' int'),
                self::XSD_NS . ' long',
                true
            ],
            [
                $schema->getGlobalType(self::XSD_NS . ' byte'),
                self::XSD_NS . ' integer',
                true
            ],
            [
                $schema->getGlobalType(self::XSD_NS . ' float'),
                self::XSD_NS . ' decimal',
                false
            ],
            [
                $schema->getGlobalType(self::XSD_NS . ' double'),
                self::XSD_NS . ' decimal',
                false
            ],
            [
                $schema->getGlobalType(self::XSD_NS . ' float'),
                self::XSD_NS . ' anySimpleType',
                true
            ],
            [
                $schema->getGlobalElement(self::FOO_NS . ' foo-int')->getType(),
                self::XSD_NS . ' byte',
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
        $isSigned,
        $isPrintable,
        $baseTypeXName,
        $primitiveTypeXName
    ): void {
        $this->assertSame($isNumeric, $type->isNumeric());
        $this->assertSame($isIntegral, $type->isIntegral());
        $this->assertSame($isSigned, $type->isSigned());
        $this->assertSame($isPrintable, $type->isPrintable());

        $this->assertEquals(
            $baseTypeXName,
            $type->getBaseType()->getXName()
        );

        $this->assertEquals(
            $primitiveTypeXName,
            $type->getPrimitiveType()->getXName()
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
                $schema->getGlobalType(self::XSD_NS . ' string'),
                false,
                false,
                false,
                true,
                new XName(self::XSD_NS, 'anySimpleType'),
                new XName(self::XSD_NS, 'string')
            ],
            [
                $schema->getGlobalType(self::XSD_NS . ' decimal'),
                true,
                false,
                true,
                true,
                new XName(self::XSD_NS, 'anySimpleType'),
                new XName(self::XSD_NS, 'decimal')
            ],
            [
                $schema->getGlobalType(self::XSD_NS . ' float'),
                true,
                false,
                true,
                true,
                new XName(self::XSD_NS, 'anySimpleType'),
                new XName(self::XSD_NS, 'float')
            ],
            [
                $schema->getGlobalType(self::XSD_NS . ' integer'),
                true,
                true,
                true,
                true,
                new XName(self::XSD_NS, 'decimal'),
                new XName(self::XSD_NS, 'decimal')
            ],
            [
                $schema->getGlobalType(self::XSD_NS . ' short'),
                true,
                true,
                true,
                true,
                new XName(self::XSD_NS, 'int'),
                new XName(self::XSD_NS, 'decimal')
            ],
            [
                $schema->getGlobalElement(self::FOO_NS . ' foo-int')->getType(),
                true,
                true,
                false,
                true,
                new XName(self::FOO_NS, 'FooUnsigned5'),
                new XName(self::XSD_NS, 'decimal')
            ],
            [
                $schema->getGlobalType(self::XSD_NS . ' hexBinary'),
                false,
                false,
                false,
                false,
                new XName(self::XSD_NS, 'anySimpleType'),
                new XName(self::XSD_NS, 'hexBinary')
            ],
            [
                $schema->getGlobalType(self::FOO_NS . ' limitedHexBinary'),
                false,
                false,
                false,
                false,
                new XName(self::XSD_NS, 'hexBinary'),
                new XName(self::XSD_NS, 'hexBinary')
            ],
            [
                $schema->getGlobalType(self::FOO_NS . ' semiNegativeFloat'),
                true,
                false,
                true,
                true,
                new XName(self::XSD_NS, 'float'),
                new XName(self::XSD_NS, 'float')
            ],
            [
                $schema->getGlobalType(self::FOO_NS . ' nonNegativeFloat'),
                true,
                false,
                false,
                true,
                new XName(self::FOO_NS, 'semiNegativeFloat'),
                new XName(self::XSD_NS, 'float')
            ]
        ];
    }

    public function testAppinfo()
    {
        $booleanTrue = new BooleanLiteral(true);

        $int6Literal = new IntegerLiteral(6, self::XSD_NS . '#byte');

        $int5Literal = new IntegerLiteral(5, self::XSD_NS . '#byte');

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
                    [
                        self::RDFS_NS . 'label',
                        new LangStringLiteral('Foo Unsigned 6', 'en')
                    ],
                    [
                        self::RDFS_NS . 'label',
                        'FooUnsigned6'
                    ],
                    [ self::BAR_NS . 'isLimitedInt', $booleanTrue ],
                    [ self::BAR_NS . 'bits', $int6Literal ],
                    [
                        self::DC_NS . 'seeAlso',
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
                        self::DC_NS . 'seeAlso',
                        new Node('http://foo.example.org/documentation/FooUnsigned5')
                    ],
                    [
                        self::RDFS_NS . 'label',
                        new LangStringLiteral('FooUnsigned5')
                    ]
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
