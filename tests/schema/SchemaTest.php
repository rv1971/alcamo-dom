<?php

namespace alcamo\dom\schema;

use PHPUnit\Framework\TestCase;
use alcamo\dom\DocumentFactory;
use alcamo\dom\extended\Document;
use alcamo\dom\schema\component\{
    AbstractSimpleType,
    AtomicType,
    Attr,
    AttrGroup,
    ComplexType,
    Element,
    EnumerationType,
    EnumerationUnionType,
    Group,
    ListType,
    Notation,
    PredefinedAttr,
    PredefinedSimpleType,
    TypeInterface,
    UnionType
};
use alcamo\dom\xsd\Document as Xsd;
use alcamo\exception\AbsoluteUriNeeded;
use alcamo\xml\XName;

class SchemaTest extends TestCase
{
    public const XML_NS = 'http://www.w3.org/XML/1998/namespace';
    public const XSD_NS = 'http://www.w3.org/2001/XMLSchema';
    public const XSI_NS = 'http://www.w3.org/2001/XMLSchema-instance';
    public const FOO_NS = 'http://foo.example.org';
    public const FOO2_NS = 'http://foo2.example.org';

    public function testNewFromDocument()
    {
        $baz = Document::newFromUrl(
            'file://'
            . str_replace(DIRECTORY_SEPARATOR, '/', dirname(__DIR__))
            . '/baz.xml'
        );

        $baz2 = Document::newFromUrl(
            'file://'
            . str_replace(DIRECTORY_SEPARATOR, '/', dirname(__DIR__))
            . '/baz2.xml'
        );

        $foo = Document::newFromUrl(
            'file://'
            . str_replace(DIRECTORY_SEPARATOR, '/', dirname(__DIR__))
            . '/foo.xml'
        );

        $schema1 = Schema::newFromDocument($baz);

        $schema2 = Schema::newFromDocument($baz2);

        $this->assertSame($schema1, $schema2);

        $this->assertSame(
            'file://'
            . str_replace(
                DIRECTORY_SEPARATOR,
                '/',
                realpath(
                    dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR
                    . 'xsd' . DIRECTORY_SEPARATOR . 'XMLSchema.xsd'
                )
            ),
            $schema1->getCacheKey()
        );

        $xsds = [ 'XMLSchema.xsd', 'xml.xsd' ];

        $this->assertSame(count($xsds), count($schema1->getXsds()));

        $i = 0;
        foreach ($schema1->getXsds() as $url => $xsd) {
            $this->assertSame($xsds[$i++], basename($url));
        }

        $this->assertEquals(
            'anyType',
            $schema1->getAnyType()->getXsdElement()->name
        );

        $this->assertEquals(
            new PredefinedSimpleType(
                $schema1,
                new XName(self::XSD_NS, 'anySimpleType'),
                $schema1->getAnyType()
            ),
            $schema1->getAnySimpleType()
        );

        $schema3 = Schema::newFromDocument($foo);

        $xsds = [
            'XMLSchema.xsd',
            'xml.xsd',
            'rdfs.xsd',
            'foo.xsd',
            'foo2.xsd',
            'foo2a.xsd',
            'xhtml-datatypes-1.xsd',
            'dc.xsd'
        ];

        $this->assertSame(count($xsds), count($schema3->getXsds()));

        $i = 0;
        foreach ($schema3->getXsds() as $url => $xsd) {
            $this->assertSame($xsds[$i++], basename($url));
        }

        foreach ($schema3->getGlobalTypes() as $xNameString => $type) {
            $this->assertInstanceOf(TypeInterface::class, $type);
            $this->assertSame($xNameString, (string)$type->getXName());
        }
    }

    public function testNewFromXsds()
    {
        $documentFactory = new DocumentFactory(
            'file://' . str_replace(DIRECTORY_SEPARATOR, '/', dirname(__DIR__))
            . '/'
        );

        $xsds = [
            $documentFactory->createFromUrl('foo.xsd'),
            $documentFactory->createFromUrl('bar.xsd'),
        ];

        $xsds2 = [
            $documentFactory->createFromUrl('foo.xsd'),
            $documentFactory->createFromUrl('component/../bar.xsd'),
        ];

        $this->assertSame($xsds[0], $xsds2[0]);

        $this->assertSame($xsds[1], $xsds2[1]);

        $schema1 = Schema::newFromXsds($xsds);

        $schema2 = Schema::newFromXsds($xsds);

        $this->assertSame($schema1, $schema2);

        $xsds = [
            'XMLSchema.xsd',
            'xml.xsd',
            'bar.xsd',
            'foo.xsd',
            'foo2.xsd',
            'foo2a.xsd',
            'rdfs.xsd',
            'xhtml-datatypes-1.xsd',
            'dc.xsd'
        ];

        $this->assertSame(count($xsds), count($schema1->getXsds()));

        $i = 0;
        foreach ($schema1->getXsds() as $url => $xsd) {
            $this->assertSame($xsds[$i++], basename($url));
        }
    }

    public function testNewFromXsdsException()
    {
        $path = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR
            . 'xsd' . DIRECTORY_SEPARATOR . 'XMLSchema.xsd';

        $this->expectException(AbsoluteUriNeeded::class);
        $this->expectExceptionMessage(
            "Relative URI \"$path\" given where absolute URI is needed"
        );

        Schema::newFromXsds([ Xsd::newFromUrl($path) ]);
    }

    public function testNegativeGetGlobal()
    {
        $schema = Schema::newFromDocument(
            Document::newFromUrl(
                'file://' . dirname(__DIR__) . '/baz.xml'
            )
        );


        $none = new XName(self::FOO_NS, 'none');

        $this->assertNull($schema->getGlobalAttr($none));
        $this->assertNull($schema->getGlobalAttrGroup($none));
        $this->assertNull($schema->getGlobalElement($none));
        $this->assertNull($schema->getGlobalGroup($none));
        $this->assertNull($schema->getGlobalNotation($none));
        $this->assertNull($schema->getGlobalType($none));
    }

    /**
     * @dataProvider getGlobalAttrProvider
     */
    public function testGetGlobalAttr($schema, $attrNs, $attrLocalName)
    {
        $xName = new XName($attrNs, $attrLocalName);

        $comp = $schema->getGlobalAttr($xName);

        $this->assertInstanceOf(Attr::class, $comp);
        $this->assertSame($schema, $comp->getSchema());
        $this->assertEquals(
            new XName(self::XSD_NS, 'attribute'),
            $comp->getXsdElement()->getXName()
        );
        $this->assertEquals($xName, $comp->getXName());
    }

    public function getGlobalAttrProvider()
    {
        $schema = Schema::newFromDocument(
            Document::newFromUrl(
                'file://' . dirname(__DIR__) . '/baz.xml'
            )
        );

        return [
            'xml:lang' =>  [ $schema, self::XML_NS, 'lang' ],
            'xml:space' => [ $schema, self::XML_NS, 'space' ],
            'xml:base' =>  [ $schema, self::XML_NS, 'base' ],
            'xml:id' =>    [ $schema, self::XML_NS, 'id' ]
        ];
    }

    /**
     * @dataProvider getGlobalAttrGroupProvider
     */
    public function testGetGlobalAttrGroup(
        $schema,
        $attrGroupNs,
        $attrGroupLocalName
    ) {
        $xName = new XName($attrGroupNs, $attrGroupLocalName);

        $comp = $schema->getGlobalAttrGroup($xName);

        $this->assertInstanceOf(AttrGroup::class, $comp);
        $this->assertSame($schema, $comp->getSchema());
        $this->assertEquals(
            new XName(self::XSD_NS, 'attributeGroup'),
            $comp->getXsdElement()->getXName()
        );
        $this->assertEquals($xName, $comp->getXName());
    }

    public function getGlobalAttrGroupProvider()
    {
        $schema = Schema::newFromDocument(
            Document::newFromUrl(
                'file://' . dirname(__DIR__) . '/baz.xml'
            )
        );

        return [
            'xml:specialAttrs' => [ $schema, self::XML_NS, 'specialAttrs' ],
            'xsd:occurs' => [ $schema, self::XSD_NS, 'occurs' ],
            'xsd:defRef' => [ $schema, self::XSD_NS, 'defRef' ]
        ];
    }

    /**
     * @dataProvider getGlobalComplexTypeProvider
     */
    public function testGetGlobalComplexType(
        $schema,
        $complexTypeNs,
        $complexTypeLocalName,
        $expectedBaseTypeLocalName
    ) {
        $xName = new XName($complexTypeNs, $complexTypeLocalName);

        $comp = $schema->getGlobalType($xName);

        $this->assertInstanceOf(ComplexType::class, $comp);
        $this->assertSame($schema, $comp->getSchema());
        $this->assertEquals(
            new XName(self::XSD_NS, 'complexType'),
            $comp->getXsdElement()->getXName()
        );
        $this->assertEquals($xName, $comp->getXName());

        $this->assertEquals(
            $expectedBaseTypeLocalName,
            $comp->getBaseType() ? $comp->getBaseType()->getXName()->getLocalName() : null
        );
    }

    public function getGlobalComplexTypeProvider()
    {
        $schema = Schema::newFromDocument(
            Document::newFromUrl(
                'file://' . dirname(__DIR__) . '/baz.xml'
            )
        );

        return [
            'xsd:openAttrs' => [ $schema, self::XSD_NS, 'openAttrs', 'anyType' ],
            'xsd:annotated' => [ $schema, self::XSD_NS, 'annotated', 'openAttrs' ],
            'xsd:attribute' => [ $schema, self::XSD_NS, 'attribute', 'annotated' ],
            'xsd:topLevelAttribute' => [ $schema, self::XSD_NS, 'topLevelAttribute', 'attribute' ],
            'xsd:complexType' => [ $schema, self::XSD_NS, 'complexType', 'annotated' ],
            'xsd:topLevelComplexType' => [ $schema, self::XSD_NS, 'topLevelComplexType', 'complexType' ],
            'xsd:localComplexType' => [ $schema, self::XSD_NS, 'localComplexType', 'complexType' ],
            'xsd:restrictionType' => [ $schema, self::XSD_NS, 'restrictionType', 'annotated' ],
            'xsd:complexRestrictionType' => [ $schema, self::XSD_NS, 'complexRestrictionType', 'restrictionType' ],
            'xsd:extensionType' => [ $schema, self::XSD_NS, 'extensionType', 'annotated' ],
            'xsd:simpleRestrictionType' => [ $schema, self::XSD_NS, 'simpleRestrictionType', 'restrictionType' ],
            'xsd:simpleExtensionType' => [ $schema, self::XSD_NS, 'simpleExtensionType', 'extensionType' ],
            'xsd:element' => [ $schema, self::XSD_NS, 'element', 'annotated' ],
            'xsd:topLevelElement' => [ $schema, self::XSD_NS, 'topLevelElement', 'element' ],
            'xsd:localElement' => [ $schema, self::XSD_NS, 'localElement', 'element' ],
            'xsd:group' => [ $schema, self::XSD_NS, 'group', 'annotated' ],
            'xsd:realGroup' => [ $schema, self::XSD_NS, 'realGroup', 'group' ],
            'xsd:namedGroup' => [ $schema, self::XSD_NS, 'namedGroup', 'realGroup' ],
            'xsd:groupRef' => [ $schema, self::XSD_NS, 'groupRef', 'realGroup' ],
            'xsd:explicitGroup' => [ $schema, self::XSD_NS, 'explicitGroup', 'group' ],
            'xsd:simpleExplicitGroup' => [ $schema, self::XSD_NS, 'simpleExplicitGroup', 'explicitGroup' ],
            'xsd:narrowMaxMin' => [ $schema, self::XSD_NS, 'narrowMaxMin', 'localElement' ],
            'xsd:all' => [ $schema, self::XSD_NS, 'all', 'explicitGroup' ],
            'xsd:wildcard' => [ $schema, self::XSD_NS, 'wildcard', 'annotated' ],
            'xsd:attributeGroup' => [ $schema, self::XSD_NS, 'attributeGroup', 'annotated' ],
            'xsd:namedAttributeGroup' => [ $schema, self::XSD_NS, 'namedAttributeGroup', 'attributeGroup' ],
            'xsd:attributeGroupRef' => [ $schema, self::XSD_NS, 'attributeGroupRef', 'attributeGroup' ],
            'xsd:keybase' => [ $schema, self::XSD_NS, 'keybase', 'annotated' ],
            'xsd:anyType' => [ $schema, self::XSD_NS, 'anyType', null ],
            'xsd:simpleType' => [ $schema, self::XSD_NS, 'simpleType', 'annotated' ],
            'xsd:topLevelSimpleType' => [ $schema, self::XSD_NS, 'topLevelSimpleType', 'simpleType' ],
            'xsd:localSimpleType' => [ $schema, self::XSD_NS, 'localSimpleType', 'simpleType' ],
            'xsd:facet' => [ $schema, self::XSD_NS, 'facet', 'annotated' ],
            'xsd:noFixedFacet' => [ $schema, self::XSD_NS, 'noFixedFacet', 'facet' ],
            'xsd:numFacet' => [ $schema, self::XSD_NS, 'numFacet', 'facet' ]
        ];
    }

    /**
     * @dataProvider getGlobalElementProvider
     */
    public function testGetGlobalElement($schema, $elementNs, $elementLocalName)
    {
        $xName = new XName($elementNs, $elementLocalName);

        $comp = $schema->getGlobalElement($xName);

        $this->assertInstanceOf(Element::class, $comp);
        $this->assertSame($schema, $comp->getSchema());
        $this->assertEquals(
            new XName(self::XSD_NS, 'element'),
            $comp->getXsdElement()->getXName()
        );
        $this->assertEquals($xName, $comp->getXName());
    }

    public function getGlobalElementProvider()
    {
        $schema = Schema::newFromDocument(
            Document::newFromUrl(
                'file://' . dirname(__DIR__) . '/baz.xml'
            )
        );

        return [
            'xsd:schema' => [ $schema, self::XSD_NS, 'schema' ],
            'xsd:anyAttribute' => [ $schema, self::XSD_NS, 'anyAttribute' ],
            'xsd:complexContent' => [ $schema, self::XSD_NS, 'complexContent' ],
            'xsd:simpleContent' => [ $schema, self::XSD_NS, 'simpleContent' ],
            'xsd:complexType' => [ $schema, self::XSD_NS, 'complexType' ],
            'xsd:element' => [ $schema, self::XSD_NS, 'element' ],
            'xsd:all' => [ $schema, self::XSD_NS, 'all' ],
            'xsd:choice' => [ $schema, self::XSD_NS, 'choice' ],
            'xsd:sequence' => [ $schema, self::XSD_NS, 'sequence' ],
            'xsd:group' => [ $schema, self::XSD_NS, 'group' ],
            'xsd:any' => [ $schema, self::XSD_NS, 'any' ],
            'xsd:attribute' => [ $schema, self::XSD_NS, 'attribute' ],
            'xsd:attributeGroup' => [ $schema, self::XSD_NS, 'attributeGroup' ],
            'xsd:include' => [ $schema, self::XSD_NS, 'include' ],
            'xsd:redefine' => [ $schema, self::XSD_NS, 'redefine' ],
            'xsd:import' => [ $schema, self::XSD_NS, 'import' ],
            'xsd:selector' => [ $schema, self::XSD_NS, 'selector' ],
            'xsd:field' => [ $schema, self::XSD_NS, 'field' ],
            'xsd:unique' => [ $schema, self::XSD_NS, 'unique' ],
            'xsd:key' => [ $schema, self::XSD_NS, 'key' ],
            'xsd:keyref' => [ $schema, self::XSD_NS, 'keyref' ],
            'xsd:notation' => [ $schema, self::XSD_NS, 'notation' ],
            'xsd:appinfo' => [ $schema, self::XSD_NS, 'appinfo' ],
            'xsd:documentation' => [ $schema, self::XSD_NS, 'documentation' ],
            'xsd:annotation' => [ $schema, self::XSD_NS, 'annotation' ]
        ];
    }

    /**
     * @dataProvider getGlobalEnumerationTypeProvider
     */
    public function testGetGlobalEnumerationType(
        $schema,
        $enumerationTypeNs,
        $enumerationTypeLocalName,
        $expectedEnumerators
    ) {
        $xName = new XName($enumerationTypeNs, $enumerationTypeLocalName);

        $comp = $schema->getGlobalType($xName);

        $this->assertInstanceOf(EnumerationType::class, $comp);
        $this->assertSame($schema, $comp->getSchema());
        $this->assertEquals($xName, $comp->getXName());

        $this->assertSame(
            count($expectedEnumerators),
            count($comp->getEnumerators())
        );

        $i = 0;
        foreach ($comp->getEnumerators() as $value => $enumerator) {
            $this->assertSame($expectedEnumerators[$i++], $value);
            $this->assertSame($value, (string)$enumerator);
        }
    }

    public function getGlobalEnumerationTypeProvider()
    {
        $schema = Schema::newFromDocument(
            Document::newFromUrl(
                'file://' . dirname(__DIR__) . '/baz.xml'
            )
        );

        return [
            'xsd:formChoice' =>
            [
                $schema,
                self::XSD_NS,
                'formChoice',
                [ 'qualified', 'unqualified' ]
            ],
            'xsd:reducedDerivationControl' => [
                $schema,
                self::XSD_NS,
                'reducedDerivationControl',
                [ 'extension', 'restriction' ]
            ],
            'xsd:typeDerivationControl' => [
                $schema,
                self::XSD_NS,
                'typeDerivationControl',
                [ 'extension', 'restriction', 'list', 'union' ]
            ],
            'xsd:derivationControl' => [
                $schema,
                self::XSD_NS,
                'derivationControl',
                [ 'substitution', 'extension', 'restriction', 'list', 'union' ]
            ]
        ];
    }

    /**
     * @dataProvider getGlobalEnumerationUnionTypeProvider
     */
    public function testGetGlobalEnumerationUnionType(
        $schema,
        $enumerationTypeNs,
        $enumerationTypeLocalName,
        $expectedEnumerators
    ) {
        $xName = new XName($enumerationTypeNs, $enumerationTypeLocalName);

        $comp = $schema->getGlobalType($xName);

        $this->assertInstanceOf(EnumerationUnionType::class, $comp);
        $this->assertSame($schema, $comp->getSchema());
        $this->assertEquals($xName, $comp->getXName());

        $this->assertSame(
            count($expectedEnumerators),
            count($comp->getEnumerators())
        );

        $i = 0;
        foreach ($comp->getEnumerators() as $value => $enumerator) {
            $this->assertSame($expectedEnumerators[$i++], $value);
            $this->assertSame($value, (string)$enumerator);
        }
    }

    public function getGlobalEnumerationUnionTypeProvider()
    {
        $schema = Schema::newFromDocument(
            Document::newFromUrl(
                'file://' . dirname(__DIR__) . '/foo.xml'
            )
        );

        return [
            'foo2:EnumUnion' => [
                $schema,
                self::FOO2_NS,
                'EnumUnion',
                [
                    'qualified',
                    'unqualified',
                    'substitution',
                    'extension',
                    'restriction',
                    'list',
                    'union',
                    'foo',
                    'bar',
                    'baz',
                    'qux',
                    'quux'
                ]
            ]
        ];
    }

    /**
     * @dataProvider getGlobalGroupProvider
     */
    public function testGetGlobalGroup($schema, $groupNs, $groupLocalName)
    {
        $xName = new XName($groupNs, $groupLocalName);

        $comp = $schema->getGlobalGroup($xName);

        $this->assertInstanceOf(Group::class, $comp);
        $this->assertSame($schema, $comp->getSchema());
        $this->assertEquals(
            new XName(self::XSD_NS, 'group'),
            $comp->getXsdElement()->getXName()
        );
        $this->assertEquals($xName, $comp->getXName());
    }

    public function getGlobalGroupProvider()
    {
        $schema = Schema::newFromDocument(
            Document::newFromUrl(
                'file://' . dirname(__DIR__) . '/baz.xml'
            )
        );

        return [
            'xsd:schemaTop' => [ $schema, self::XSD_NS, 'schemaTop' ],
            'xsd:redefinable' => [ $schema, self::XSD_NS, 'redefinable' ],
            'xsd:typeDefParticle' => [ $schema, self::XSD_NS, 'typeDefParticle' ],
            'xsd:nestedParticle' => [ $schema, self::XSD_NS, 'nestedParticle' ],
            'xsd:particle' => [ $schema, self::XSD_NS, 'particle' ],
            'xsd:attrDecls' => [ $schema, self::XSD_NS, 'attrDecls' ],
            'xsd:complexTypeModel' => [ $schema, self::XSD_NS, 'complexTypeModel' ],
            'xsd:allModel' => [ $schema, self::XSD_NS, 'allModel' ],
            'xsd:identityConstraint' => [ $schema, self::XSD_NS, 'identityConstraint' ],
            'xsd:simpleDerivation' => [ $schema, self::XSD_NS, 'simpleDerivation' ],
            'xsd:facets' => [ $schema, self::XSD_NS, 'facets' ],
            'xsd:simpleRestrictionModel' => [ $schema, self::XSD_NS, 'simpleRestrictionModel' ]
        ];
    }

    /**
     * @dataProvider getGlobalListTypeProvider
     */
    public function testGetGlobalListType(
        $schema,
        $listTypeNs,
        $listTypeLocalName,
        $itemTypeLocalName
    ) {
        $xName = new XName($listTypeNs, $listTypeLocalName);
        $comp = $schema->getGlobalType($xName);

        $this->assertInstanceOf(ListType::class, $comp);
        $this->assertSame($schema, $comp->getSchema());
        $this->assertEquals($xName, $comp->getXName());
        $this->assertInstanceOf(TypeInterface::class, $comp->getItemType());
        $this->assertEquals(
            $itemTypeLocalName,
            $comp->getItemType()->getXName() ? $comp->getItemType()->getXName()->getLocalName() : null
        );
    }

    public function getGlobalListTypeProvider()
    {
        $bazSchema = Schema::newFromDocument(
            Document::newFromUrl(
                'file://' . dirname(__DIR__) . '/baz.xml'
            )
        );

        $fooSchema = Schema::newFromDocument(
            Document::newFromUrl(
                'file://' . dirname(__DIR__) . '/foo.xml'
            )
        );

        return [
            'xsd:IDREFS' => [ $bazSchema, self::XSD_NS, 'IDREFS', 'IDREF' ],
            'xsd:ENTITIES' => [ $bazSchema, self::XSD_NS, 'ENTITIES', 'ENTITY' ],
            'xsd:NMTOKENS' => [ $bazSchema, self::XSD_NS, 'NMTOKENS', 'NMTOKEN' ],
            'foo2:ListOfNamedItemType' => [
                $fooSchema,
                self::FOO2_NS,
                'ListOfNamedItemType',
                'integer'
            ],
            'foo2:ListOfAnonymousItemType' => [
                $fooSchema,
                self::FOO2_NS,
                'ListOfAnonymousItemType',
                null
            ],
            'foo2:DerivedFromList' => [
                $fooSchema,
                self::FOO2_NS,
                'DerivedFromList',
                'integer'
            ]
        ];
    }

    /**
     * @dataProvider getGlobalNotationProvider
     */
    public function testGetGlobalNotation($schema, $notationNs, $notationLocalName)
    {
        $xName = new XName($notationNs, $notationLocalName);

        $comp = $schema->getGlobalNotation($xName);

        $this->assertInstanceOf(Notation::class, $comp);
        $this->assertSame($schema, $comp->getSchema());
        $this->assertEquals(
            new XName(self::XSD_NS, 'notation'),
            $comp->getXsdElement()->getXName()
        );
        $this->assertEquals($xName, $comp->getXName());
    }

    public function getGlobalNotationProvider()
    {
        $schema = Schema::newFromDocument(
            Document::newFromUrl(
                'file://' . dirname(__DIR__) . '/baz.xml'
            )
        );

        return [
            'xsd:XMLSchemaStructures' => [ $schema, self::XSD_NS, 'XMLSchemaStructures' ],
            'xsd:XML' => [ $schema, self::XSD_NS, 'XML' ]
        ];
    }

    /**
     * @dataProvider getGlobalPredefinedAttrProvider
     */
    public function testGetGlobalPredefinedAttr(
        $schema,
        $predefinedAttrNs,
        $predefinedAttrLocalName,
        $expectedTypeXName
    ) {
        $xName = new XName($predefinedAttrNs, $predefinedAttrLocalName);

        $comp = $schema->getGlobalAttr($xName);

        $this->assertInstanceOf(PredefinedAttr::class, $comp);
        $this->assertSame($schema, $comp->getSchema());
        $this->assertEquals($xName, $comp->getXName());
        $this->assertEquals(
            $expectedTypeXName,
            $comp->getType()->getXName()
        );
    }

    public function getGlobalPredefinedAttrProvider()
    {
        $schema = Schema::newFromDocument(
            Document::newFromUrl(
                'file://' . dirname(__DIR__) . '/baz.xml'
            )
        );

        return [
            'xsd:' => [
                $schema, self::XSI_NS, 'type', new XName(self::XSD_NS, 'QName')
            ]
        ];
    }

    /**
     * @dataProvider getGlobalPredefinedSimpleTypeProvider
     */
    public function testGetGlobalPredefinedSimpleType(
        $schema,
        $predefinedTypeNs,
        $predefinedTypeLocalName,
        $expectedBaseTypeXName
    ) {
        $xName = new XName($predefinedTypeNs, $predefinedTypeLocalName);

        $comp = $schema->getGlobalType($xName);

        $this->assertInstanceOf(PredefinedSimpleType::class, $comp);
        $this->assertSame($schema, $comp->getSchema());
        $this->assertEquals($xName, $comp->getXName());
        $this->assertEquals(
            $expectedBaseTypeXName,
            $comp->getBaseType()->getXName()
        );
    }

    public function getGlobalPredefinedSimpleTypeProvider()
    {
        $schema = Schema::newFromDocument(
            Document::newFromUrl(
                'file://' . dirname(__DIR__) . '/baz.xml'
            )
        );

        return [
            'xsd:anySimpleType' => [
                $schema,
                self::XSD_NS,
                'anySimpleType',
                new XName(self::XSD_NS, 'anyType')
            ]
        ];
    }

    /**
     * @dataProvider getGlobalSimpleTypeProvider
     */
    public function testGetGlobalSimpleType(
        $schema,
        $simpleTypeNs,
        $simpleTypeLocalName,
        $expectedBaseTypeLocalName
    ) {
        $xName = new XName($simpleTypeNs, $simpleTypeLocalName);

        $comp = $schema->getGlobalType($xName);

        $this->assertInstanceOf(AbstractSimpleType::class, $comp);
        $this->assertSame($schema, $comp->getSchema());
        $this->assertEquals(
            new XName(self::XSD_NS, 'simpleType'),
            $comp->getXsdElement()->getXName()
        );
        $this->assertEquals($xName, $comp->getXName());

        switch (true) {
            case !isset($expectedBaseTypeLocalName):
                $this->assertNull($comp->getBaseType());
                break;

            case $expectedBaseTypeLocalName === true:
                $this->assertInstanceOf(TypeInterface::class, $comp->getBaseType());
                $this->assertNull($comp->getBaseType()->getXName());
                break;

            default:
                $this->assertEquals(
                    $expectedBaseTypeLocalName,
                    $comp->getBaseType()->getXName()->getLocalName()
                );
        }
    }

    public function getGlobalSimpleTypeProvider()
    {
        $schema = Schema::newFromDocument(
            Document::newFromUrl(
                'file://' . dirname(__DIR__) . '/baz.xml'
            )
        );

        return [
            'xsd:formChoice' => [ $schema, self::XSD_NS, 'formChoice', 'NMTOKEN' ],
            'xsd:reducedDerivationControl' => [
                $schema,
                self::XSD_NS,
                'reducedDerivationControl',
                'derivationControl'
            ],
            'xsd:derivationSet' => [ $schema, self::XSD_NS, 'derivationSet', null ],
            'xsd:typeDerivationControl' => [
                $schema,
                self::XSD_NS,
                'typeDerivationControl',
                'derivationControl'
            ],
            'xsd:fullDerivationSet' => [ $schema, self::XSD_NS, 'fullDerivationSet', null ],
            'xsd:allNNI' => [ $schema, self::XSD_NS, 'allNNI', null ],
            'xsd:blockSet' => [ $schema, self::XSD_NS, 'blockSet', null ],
            'xsd:namespaceList' => [ $schema, self::XSD_NS, 'namespaceList', null ],
            'xsd:public' => [ $schema, self::XSD_NS, 'public', 'token' ],
            'xsd:string' => [ $schema, self::XSD_NS, 'string', 'anySimpleType' ],
            'xsd:boolean' => [ $schema, self::XSD_NS, 'boolean', 'anySimpleType' ],
            'xsd:float' => [ $schema, self::XSD_NS, 'float', 'anySimpleType' ],
            'xsd:double' => [ $schema, self::XSD_NS, 'double', 'anySimpleType' ],
            'xsd:decimal' => [ $schema, self::XSD_NS, 'decimal', 'anySimpleType' ],
            'xsd:duration' => [ $schema, self::XSD_NS, 'duration', 'anySimpleType' ],
            'xsd:dateTime' => [ $schema, self::XSD_NS, 'dateTime', 'anySimpleType' ],
            'xsd:time' => [ $schema, self::XSD_NS, 'time', 'anySimpleType' ],
            'xsd:date' => [ $schema, self::XSD_NS, 'date', 'anySimpleType' ],
            'xsd:gYearMonth' => [ $schema, self::XSD_NS, 'gYearMonth', 'anySimpleType' ],
            'xsd:gYear' => [ $schema, self::XSD_NS, 'gYear', 'anySimpleType' ],
            'xsd:gMonthDay' => [ $schema, self::XSD_NS, 'gMonthDay', 'anySimpleType' ],
            'xsd:gDay' => [ $schema, self::XSD_NS, 'gDay', 'anySimpleType' ],
            'xsd:gMonth' => [ $schema, self::XSD_NS, 'gMonth', 'anySimpleType' ],
            'xsd:hexBinary' => [ $schema, self::XSD_NS, 'hexBinary', 'anySimpleType' ],
            'xsd:base64Binary' => [ $schema, self::XSD_NS, 'base64Binary', 'anySimpleType' ],
            'xsd:anyURI' => [ $schema, self::XSD_NS, 'anyURI', 'anySimpleType' ],
            'xsd:QName' => [ $schema, self::XSD_NS, 'QName', 'anySimpleType' ],
            'xsd:NOTATION' => [ $schema, self::XSD_NS, 'NOTATION', 'anySimpleType' ],
            'xsd:normalizedString' => [ $schema, self::XSD_NS, 'normalizedString', 'string' ],
            'xsd:token' => [ $schema, self::XSD_NS, 'token', 'normalizedString' ],
            'xsd:language' => [ $schema, self::XSD_NS, 'language', 'token' ],
            'xsd:IDREFS' => [ $schema, self::XSD_NS, 'IDREFS', true ],
            'xsd:ENTITIES' => [ $schema, self::XSD_NS, 'ENTITIES', true ],
            'xsd:NMTOKEN' => [ $schema, self::XSD_NS, 'NMTOKEN', 'token' ],
            'xsd:NMTOKENS' => [ $schema, self::XSD_NS, 'NMTOKENS', true ],
            'xsd:Name' => [ $schema, self::XSD_NS, 'Name', 'token' ],
            'xsd:NCName' => [ $schema, self::XSD_NS, 'NCName', 'Name' ],
            'xsd:ID' => [ $schema, self::XSD_NS, 'ID', 'NCName' ],
            'xsd:IDREF' => [ $schema, self::XSD_NS, 'IDREF', 'NCName' ],
            'xsd:ENTITY' => [ $schema, self::XSD_NS, 'ENTITY', 'NCName' ],
            'xsd:integer' => [ $schema, self::XSD_NS, 'integer', 'decimal' ],
            'xsd:nonPositiveInteger' => [ $schema, self::XSD_NS, 'nonPositiveInteger', 'integer' ],
            'xsd:negativeInteger' => [ $schema, self::XSD_NS, 'negativeInteger', 'nonPositiveInteger' ],
            'xsd:long' => [ $schema, self::XSD_NS, 'long', 'integer' ],
            'xsd:int' => [ $schema, self::XSD_NS, 'int', 'long' ],
            'xsd:short' => [ $schema, self::XSD_NS, 'short', 'int' ],
            'xsd:byte' => [ $schema, self::XSD_NS, 'byte', 'short' ],
            'xsd:nonNegativeInteger' => [ $schema, self::XSD_NS, 'nonNegativeInteger', 'integer' ],
            'xsd:unsignedLong' => [ $schema, self::XSD_NS, 'unsignedLong', 'nonNegativeInteger' ],
            'xsd:unsignedInt' => [ $schema, self::XSD_NS, 'unsignedInt', 'unsignedLong' ],
            'xsd:unsignedShort' => [ $schema, self::XSD_NS, 'unsignedShort', 'unsignedInt' ],
            'xsd:unsignedByte' => [ $schema, self::XSD_NS, 'unsignedByte', 'unsignedShort' ],
            'xsd:positiveInteger' => [ $schema, self::XSD_NS, 'positiveInteger', 'nonNegativeInteger' ],
            'xsd:derivationControl' => [ $schema, self::XSD_NS, 'derivationControl', 'NMTOKEN' ],
            'xsd:simpleDerivationSet' => [ $schema, self::XSD_NS, 'simpleDerivationSet', null ]
        ];
    }

    /**
     * @dataProvider getGlobalUnionTypeProvider
     */
    public function testGetGlobalUnionType(
        $schema,
        $unionTypeNs,
        $unionTypeLocalName,
        $expectedMemberTypes,
        $isEnumerationUnion
    ) {
        $xName = new XName($unionTypeNs, $unionTypeLocalName);

        $comp = $schema->getGlobalType($xName);

        $this->assertInstanceOf(UnionType::class, $comp);
        $this->assertSame($schema, $comp->getSchema());
        $this->assertEquals($xName, $comp->getXName());

        $this->assertSame(
            count($expectedMemberTypes),
            count($comp->getMemberTypes())
        );

        $i = 0;
        foreach ($comp->getMemberTypes() as $memberType) {
            [ $expectedClass, $expectedLocalName ] = $expectedMemberTypes[$i++];

            $this->assertInstanceOf($expectedClass, $memberType);

            if (isset($expectedLocalName)) {
                $this->assertSame(
                    $expectedLocalName,
                    $memberType->getXName()->getLocalName()
                );
            } else {
                $this->assertNull($memberType->getXName());
            }
        }

        $this->assertSame(
            $isEnumerationUnion,
            $comp instanceof EnumerationUnionType
        );
    }

    public function getGlobalUnionTypeProvider()
    {
        $bazSchema = Schema::newFromDocument(
            Document::newFromUrl(
                'file://' . dirname(__DIR__) . '/baz.xml'
            )
        );

        $fooSchema = Schema::newFromDocument(
            Document::newFromUrl(
                'file://' . dirname(__DIR__) . '/foo.xml'
            )
        );

        return [
            'xsd:derivationSet' => [
                $bazSchema,
                self::XSD_NS,
                'derivationSet',
                [
                    [ EnumerationType::class, null ],
                    [ ListType::class, null ]
                ],
                false
            ],
            'xsd:fullDerivationSet' => [
                $bazSchema,
                self::XSD_NS,
                'fullDerivationSet',
                [
                    [ EnumerationType::class, null ],
                    [ ListType::class, null ]
                ],
                false
            ],
            'xsd:allNNI' => [
                $bazSchema,
                self::XSD_NS,
                'allNNI',
                [
                    [ AtomicType::class, 'nonNegativeInteger' ],
                    [ EnumerationType::class, null ],
                ],
                false
            ],
            'xsd:blockSet' => [
                $bazSchema,
                self::XSD_NS,
                'blockSet',
                [
                    [ EnumerationType::class, null ],
                    [ ListType::class, null ]
                ],
                false
            ],
            'xsd:namespaceList' => [
                $bazSchema,
                self::XSD_NS,
                'namespaceList',
                [
                    [ EnumerationType::class, null ],
                    [ ListType::class, null ]
                ],
                false
            ],
            'xsd:simpleDerivationSet' => [
                $bazSchema,
                self::XSD_NS,
                'simpleDerivationSet',
                [
                    [ EnumerationType::class, null ],
                    [ ListType::class, null ]
                ],
                false
            ],
            'foo2:UnionOfNamed' => [
                $fooSchema,
                self::FOO2_NS,
                'UnionOfNamed',
                [
                    [ EnumerationType::class, 'formChoice' ],
                    [ UnionType::class, 'derivationSet' ],
                    [ AtomicType::class, 'Literal' ]
                ],
                false
            ],
            'foo2:EnumUnion' => [
                $fooSchema,
                self::FOO2_NS,
                'EnumUnion',
                [
                    [ EnumerationType::class, 'formChoice' ],
                    [ EnumerationType::class, 'derivationControl' ],
                    [ EnumerationType::class, null ],
                    [ EnumerationType::class, null ]
                ],
                true
            ]
        ];
    }

    /**
     * @dataProvider lookupElementTypeProvider
     */
    public function testLookupElementType(
        $schema,
        $element,
        $expectedFile,
        $expectedNodePath
    ) {
        $type = $schema->lookupElementType($element);

        if (isset($expectedFile)) {
            $this->assertSame(
                $expectedFile,
                basename($type->getXsdElement()->ownerDocument->documentURI)
            );

            $this->assertSame(
                $expectedNodePath,
                $type->getXsdElement()->getNodePath()
            );
        } else {
            $this->assertNull($type);
        }
    }

    public function lookupElementTypeProvider()
    {
        $foo = Document::newFromUrl(
            'file://' . dirname(__DIR__) . '/foo.xml'
        )->conserve();

        $schema = Schema::newFromDocument($foo);

        return [
            'explicit' => [
                $schema,
                $foo['x'],
                'foo.xsd',
                '/xsd:schema/xsd:complexType[3]'
            ],
            'global' => [
                $schema,
                $foo->documentElement->firstChild,
                'rdfs.xsd',
                '/xsd:schema/xsd:element[1]/xsd:complexType'
            ],
            'local' => [
                $schema,
                $foo['a'],
                'foo.xsd',
                '/xsd:schema/xsd:complexType[3]'
                . '/xsd:complexContent/xsd:extension/xsd:sequence/xsd:element'
                . '/xsd:complexType'
            ],
            'parent-unknown' => [
                $schema,
                $foo['quux'],
                'XMLSchema.xsd',
                '/xs:schema/xs:complexType[29]'
            ],
            'element-unknown' => [
                $schema,
                $foo['qux'],
                'XMLSchema.xsd',
                '/xs:schema/xs:complexType[29]'
            ]
        ];
    }
}
