<?php

namespace alcamo\dom\schema;

use alcamo\collection\{Collection, ReadonlyCollection};
use alcamo\exception\DataValidationFailed;
use alcamo\rdf_literal\Lang;
use alcamo\rdfa\ImmutableRdfaData;
use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class FixedDomainMapWorkbenchTest extends TestCase
{
    public const BASE_PATH = __DIR__ . DIRECTORY_SEPARATOR;

    public const FOO_NS = 'http://foo.example.org/';

    private static $fileUriFactory_;
    private static $xsdText_;
    private static $schema_;
    private static $schema2_;

    public static function setUpBeforeClass(): void
    {
        self::$fileUriFactory_ = new FileUriFactory();

        self::$xsdText_ = file_get_contents(self::BASE_PATH . 'collection.xsd');

        $schemaFactory = new SchemaFactory();

        self::$schema_ = $schemaFactory->getMainSchema();

        self::$schema2_ = $schemaFactory->createFromXsds(
            [
                $schemaFactory->getDocumentFactory()
                    ->createFromXmlText(self::$xsdText_)
            ]
        );
    }

    public function testBasics(): void
    {
        $xsdUri =
            self::$fileUriFactory_->create(self::BASE_PATH . 'collection.xsd');

        $collectionWorkbench = new FixedDomainMapWorkbench(
            self::$schema_->createElementFromUri($xsdUri->withFragment('foo'))
        );

        $this->assertSame((string)$xsdUri, $collectionWorkbench->getXsdUri());

        $this->assertNull($collectionWorkbench->getXsdText());

        $this->assertSame(
            'foo',
            $collectionWorkbench->getDocPrototype()->documentElement->localName
        );

        $this->assertSame(
            FixedDomainMapWorkbench::VALIDATE,
            $collectionWorkbench->getFlags()
        );

        $collectionWorkbench2 = new FixedDomainMapWorkbench(
            self::$schema2_->getGlobalElement(self::FOO_NS . ' foo2'),
            0,
            self::$xsdText_
        );

        $this->assertSame(
            self::$xsdText_,
            $collectionWorkbench2->getXsdText()
        );

        $this->assertSame(
            'foo2',
            $collectionWorkbench2->getDocPrototype()->documentElement->localName
        );

        $this->assertSame(0, $collectionWorkbench2->getFlags());
    }

    /**
     * @dataProvider filterIterableProvider
     */
    public function testFilterIterable(
        $elementId,
        $input,
        $expectedOutput
    ): void {
        $collectionWorkbench = new FixedDomainMapWorkbench(
            self::$schema_->createElementFromUri(
                self::$fileUriFactory_
                    ->create(self::BASE_PATH . 'collection.xsd')
                    ->withFragment($elementId)
            )
        );

        $output = $collectionWorkbench->filterIterable($input);

        $this->assertSame(array_keys($expectedOutput), array_keys($output));

        foreach ($expectedOutput as $key => $expectedValue) {
            switch ($key) {
                case 'lang':
                    $this->assertSame(
                        get_class($expectedValue),
                        get_class($output[$key])
                    );

                    $this->assertEquals($expectedValue, $output[$key]);
                    break;

                default:
                    $this->assertSame($expectedValue, $output[$key]);
            }
        }

        $collectionWorkbench2 = new FixedDomainMapWorkbench(
            self::$schema2_->getGlobalElement(self::FOO_NS . " $elementId"),
            null,
            self::$xsdText_
        );

        $output = $collectionWorkbench2->filterIterable($input);

        $this->assertSame(array_keys($expectedOutput), array_keys($output));

        foreach ($expectedOutput as $key => $expectedValue) {
            switch ($key) {
                case 'lang':
                    $this->assertSame(
                        get_class($expectedValue),
                        get_class($output[$key])
                    );

                    $this->assertEquals($expectedValue, $output[$key]);
                    break;

                default:
                    $this->assertSame($expectedValue, $output[$key]);
            }
        }
    }

    public function filterIterableProvider(): array
    {
        return [
            [
                'foo',
                [
                    'isFooish' => 'true',
                    'name' => 'Foo',
                    'numbers' => '42 43 44',
                    'no' => '42'
                ],
                [
                    'no' => 42,
                    'name' => 'Foo',
                    'isFooish' => true,
                    'lang' => Lang::newFromString('is'),
                    'numbers' => [ 42 => 42, 43 => 43, 44 => 44 ]
                ]
            ],
            [
                'foo2',
                [
                    'lang' => Lang::newFromString('it-AR'),
                    'name' => 'Foo2',
                    'numbers' => null,
                    'no' => '43',
                    'isFooish' => 'false',
                    'bar' => 'BAR'
                ],
                [
                    'no' => 43,
                    'name' => 'Foo2',
                    'isFooish' => false,
                    'lang' => Lang::newFromString('it-AR')
                ]
            ]
        ];
    }

    public function testFilterIterableException1(): void
    {
        $fooElement =
            self::$schema_->getGlobalElement(self::FOO_NS . ' foo2');

        /* flags without VALIDATE */
        $collectionWorkbench = new FixedDomainMapWorkbench($fooElement, 0);

        $data2 = [ 'qux' => 'QUX' ];

        $data3 = $collectionWorkbench->filterIterable(
            [
                'isFooish' => 'true',
                'lang' => Lang::newFromString('is')
            ],
            $data2
        );

        $this->assertSame($data3, $data2);

        $this->assertEquals(
            [
                'qux' => 'QUX',
                'isFooish' => true,
                'lang' => Lang::newFromString('is')
            ],
            $data2
        );

        $collectionWorkbench = new FixedDomainMapWorkbench($fooElement);

        $this->expectException(DataValidationFailed::class);

        $this->expectExceptionMessage(
            "The attribute 'no' is required but missing."
        );

        $collectionWorkbench->filterIterable([ 'isFooish' => 'true' ]);
    }

    public function testFilterIterableException2(): void
    {
        $fooElement =
            self::$schema_->getGlobalElement(self::FOO_NS . ' foo');

        $collectionWorkbench = new FixedDomainMapWorkbench($fooElement);

        $this->expectException(DataValidationFailed::class);

        $this->expectExceptionMessage(
            "The attribute 'baz' is not allowed."
        );

        $collectionWorkbench->filterIterable(
            [ 'no' => '12345', 'name' => 'myfoo', 'baz' => 42 ]
        );
    }

    public function testFilterIterableException3(): void
    {
        $fooElement =
            self::$schema_->getGlobalElement(self::FOO_NS . ' foo');

        $collectionWorkbench = new FixedDomainMapWorkbench($fooElement);

        $this->expectException(DataValidationFailed::class);

        $this->expectExceptionMessage(
            "attribute 'name': 'my foo' is not a valid value "
                . "of the atomic type 'xs:NCName'."
        );

        $collectionWorkbench->filterIterable(
            [ 'no' => '12345', 'name' => 'my foo' ]
        );
    }

    public function testFilterIterableException4(): void
    {
        $fooElement =
            self::$schema2_->getGlobalElement(self::FOO_NS . ' foo');

        $collectionWorkbench = new FixedDomainMapWorkbench(
            $fooElement,
            null,
            self::$xsdText_
        );

        $this->expectException(DataValidationFailed::class);

        $this->expectExceptionMessage(
            "attribute 'name': 'my foo' is not a valid value "
                . "of the atomic type 'xs:NCName'."
        );

        $collectionWorkbench->filterIterable(
            [ 'no' => '12345', 'name' => 'my foo' ]
        );
    }

    public function testCreateCollectionFromString(): void
    {
        $collectionWorkbench = new FixedDomainMapWorkbench(
            self::$schema_->getGlobalElement(self::FOO_NS . ' foo2')
        );

        $input = 'name=lorem-ipsum,no=1';

        $collection = $collectionWorkbench->createCollectionFromString($input);

        $this->assertSame(ReadonlyCollection::class, get_class($collection));

        $this->assertEquals(
            new ReadonlyCollection(
                [ 'no' => 1, 'name' => 'lorem-ipsum', 'lang' => 'is' ]
            ),
            $collection
        );

        $collection2 = $collectionWorkbench
            ->createCollectionFromString($input, Collection::class);

        $this->assertSame(Collection::class, get_class($collection2));

        $this->assertEquals(
            new Collection(
                [ 'no' => 1, 'name' => 'lorem-ipsum', 'lang' => 'is' ]
            ),
            $collection2
        );
    }

    /**
     * @dataProvider getRdfaDataProvider
     */
    public function testGetRdfaData(
        $elementId,
        $attrName,
        $expectedRdfaData
    ): void {
        $collectionWorkbench = new FixedDomainMapWorkbench(
            self::$schema_->getGlobalElement(self::FOO_NS . " $elementId")
        );

        $this->assertEquals(
            ImmutableRdfaData::newFromIterable($expectedRdfaData),
            $collectionWorkbench->getRdfaData($attrName)
        );

        $this->assertSame(
            $collectionWorkbench->getAttrs()[$attrName]->getXsdElement()
                ->getRdfaData(),
            $collectionWorkbench->getRdfaData($attrName)
        );
    }

    public function getRdfaDataProvider(): array
    {
        return [
            [
                'foo2',
                'no',
                [ [ 'rdfs:label', 'Number' ] ]
            ]
        ];
    }

    public function testNewFromElementUri(): void
    {
        $collectionWorkbench1 = new FixedDomainMapWorkbench(
            self::$schema_->getGlobalElement(self::FOO_NS . ' foo2'),
            0
        );

        $collectionWorkbench2 = FixedDomainMapWorkbench::newFromElementUri(
            (new FileUriFactory())->create(self::BASE_PATH . 'collection.xsd')
                . '#foo2',
            0
        );

        $this->assertEquals($collectionWorkbench1, $collectionWorkbench2);
    }

    public function testNewFromXsdTextAndId(): void
    {
        $collectionWorkbench1 = new FixedDomainMapWorkbench(
            self::$schema_->getGlobalElement(self::FOO_NS . ' foo2'),
            0,
            self::$xsdText_
        );

        $collectionWorkbench2 = FixedDomainMapWorkbench::newFromXsdTextAndId(
            self::$xsdText_,
            'foo2',
            0
        );

        $this->assertEquals($collectionWorkbench1, $collectionWorkbench2);
    }
}
