<?php

namespace alcamo\dom\schema;

use alcamo\dom\Document;
use alcamo\dom\schema\component\{
    AtomicType,
    ComplexType,
    PredefinedAnySimpleType,
    TypeInterface,
    UnionType
};
use alcamo\uri\FileUriFactory;
use alcamo\xml\XName;
use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase
{
    private static $schema_;

    public static function setUpBeforeClass(): void
    {
        self::$schema_ = (new SchemaFactory())->createFromDocument(
            Document::newFromUri(
                (new FileUriFactory())
                    ->create(__DIR__ . DIRECTORY_SEPARATOR . 'foo.xml')
            )
        );
    }

    public function testGetAnyType(): void
    {
        $anySimpleType = self::$schema_->getAnySimpleType();

        $this->assertInstanceOf(PredefinedAnySimpleType::class, $anySimpleType);

        $anyType = self::$schema_->getAnyType();

        $this->assertSame($anySimpleType->getBaseType(), $anyType);

        $this->assertEquals(
            new XName(Schema::XSD_NS, 'anyType'),
            $anyType->getXName()
        );
    }

    /**
     * @dataProvider getGlobalAttrProvider
     */
    public function testGetGlobalAttr(
        $xNameString,
        $expectedTypeClass,
        $expectedTypeXName
    ): void {
        $attr = self::$schema_->getGlobalAttr($xNameString);

        $this->assertSame($attr, self::$schema_->getGlobalAttr($xNameString));

        $type = $attr->getType();

        $this->assertSame($type, $attr->getType());

        $this->assertInstanceOf($expectedTypeClass, $type);

        $this->assertSame($expectedTypeXName, (string)$type->getXName());
    }

    public function getGlobalAttrProvider(): array
    {
        return [
            [ Schema::XML_NS . ' lang', UnionType::class, '' ],
            [
                Schema::XML_NS . ' base',
                AtomicType::class,
                Schema::XSD_NS . ' anyURI'
            ]
        ];
    }

    /**
     * @dataProvider getGlobalAttrGroupProvider
     */
    public function testGetGlobalAttrGroup(
        $xNameString,
        $expectedAttrXNames
    ): void {
        $attrGroup = self::$schema_->getGlobalAttrGroup($xNameString);

        $this->assertSame(
            $attrGroup,
            self::$schema_->getGlobalAttrGroup($xNameString)
        );

        $this->assertSame(
            $expectedAttrXNames,
            array_keys($attrGroup->getAttrs())
        );
    }

    public function getGlobalAttrGroupProvider(): array
    {
        return [
            [ Schema::XSD_NS . ' occurs', [ 'minOccurs', 'maxOccurs' ] ],
            [ Schema::XSD_NS . ' defRef', [ 'name', 'ref' ] ]
        ];
    }

    /**
     * @dataProvider getGlobalElementProvider
     */
    public function testGetGlobalElement(
        $xNameString,
        $expectedTypeClass,
        $expectedTypeXName
    ): void {
        $element = self::$schema_->getGlobalElement($xNameString);

        $this->assertSame(
            $element,
            self::$schema_->getGlobalElement($xNameString)
        );

        $type = $element->getType();

        $this->assertSame($type, $element->getType());

        $this->assertInstanceOf($expectedTypeClass, $type);

        $this->assertSame($expectedTypeXName, (string)$type->getXName());
    }

    public function getGlobalElementProvider(): array
    {
        return [
            [ Schema::XSD_NS . ' schema', ComplexType::class, '' ],
            [
                Schema::XSD_NS . ' anyAttribute',
                ComplexType::class,
                Schema::XSD_NS . ' wildcard',
            ]
        ];
    }

    /**
     * @dataProvider getGlobalGroupProvider
     */
    public function testGetGlobalGroup(
        $xNameString,
        $expectedElementXNames
    ): void {
        $group = self::$schema_->getGlobalGroup($xNameString);

        $this->assertSame(
            $group,
            self::$schema_->getGlobalGroup($xNameString)
        );

        $this->assertSame(
            $expectedElementXNames,
            array_keys($group->getElements())
        );
    }

    public function getGlobalGroupProvider(): array
    {
        return [
            [
                Schema::XSD_NS . ' schemaTop',
                [
                    Schema::XSD_NS . ' simpleType',
                    Schema::XSD_NS . ' complexType',
                    Schema::XSD_NS . ' group',
                    Schema::XSD_NS . ' attributeGroup',
                    Schema::XSD_NS . ' element',
                    Schema::XSD_NS . ' attribute',
                    Schema::XSD_NS . ' notation'
                ]
            ]
        ];
    }

    /**
     * @dataProvider getGlobalNotationProvider
     */
    public function testGetGlobalNotation($xNameString, $expectedPublic): void
    {
        $this->assertSame(
            $expectedPublic,
            self::$schema_->getGlobalNotation($xNameString)->getXsdElement()
                ->public
        );
    }

    public function getGlobalNotationProvider(): array
    {
        return [
            [ 'http://foo.example.org jpeg', 'image/jpeg' ]
        ];
    }

    /**
     * @dataProvider getGlobalTypeProvider
     */
    public function testGetGlobalType($xNameString, $expectedTypeClass): void
    {
        $type = self::$schema_->getGlobalType($xNameString);

        $this->assertSame(
            $type,
            self::$schema_->getGlobalType($xNameString)
        );

        $this->assertInstanceOf($expectedTypeClass, $type);
    }

    public function getGlobalTypeProvider(): array
    {
        return [
            [ Schema::XSD_NS . ' allNNI', UnionType::class ],
            [ Schema::XSD_NS . ' attribute', ComplexType::class ]
        ];
    }

    public function testGetGlobalTypes(): void
    {
        $globalTypes = self::$schema_->getGlobalTypes();

        $stats = [];

        foreach ($globalTypes as $xNameString => $type) {
            $this->assertInstanceOf(TypeInterface::class, $type);

            $this->assertSame($xNameString, (string)$type->getXName());

            $this->assertSame(
                $type,
                self::$schema_->getGlobalType($xNameString)
            );

            $nsName = $type->getXName()->getNsName();

            if (isset($stats[$nsName])) {
                $stats[$nsName]++;
            } else {
                $stats[$nsName] = 1;
            }
        }

        ksort($stats);

        $this->assertSame(
            [
                'http://foo.example.org' => 4,
                Schema::XH11D_NS => 40,
                Schema::XSD_NS => 92
            ],
            $stats
        );
    }

    /**
     * @dataProvider lookupElementTypeProvider
     */
    public function testLookupElementType($id, $expectedTypeLineNo): void
    {
        $fileUriFactory = new FileUriFactory();

        $schema = (new SchemaFactory())->createFromUris(
            [
                $fileUriFactory
                    ->create(__DIR__ . DIRECTORY_SEPARATOR . 'bar.xsd')
            ]
        );

        $doc = $schema->getDocumentFactory()->createFromUri(
            $fileUriFactory->create(__DIR__ . DIRECTORY_SEPARATOR . 'bar.xml')
        );

        $element = $doc[$id];

        if (isset($expectedTypeLineNo)) {
            $this->assertSame(
                $expectedTypeLineNo,
                $schema->lookupElementType($element)->getXsdElement()
                    ->getLineNo()
            );
        } else {
            $this->assertSame(
                $schema->getAnyType(),
                $schema->lookupElementType($element)
            );
        }
    }

    public function lookupElementTypeProvider(): array
    {
        return [
            [ 'bar', 14 ],
            [ 'annotation', 1286 ],
            [ 'appinfo', 1259 ],
            [ 'corge', null ],
            [ 'empty', 31 ],
            [ 'qux', 63 ],
            [ 'quux', 39 ]
        ];
    }
}
