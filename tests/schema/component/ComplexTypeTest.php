<?php

namespace alcamo\dom\schema\component;

use PHPUnit\Framework\TestCase;
use alcamo\dom\extended\Document;
use alcamo\dom\schema\Schema;
use alcamo\xml\XName;

class ComplexTypeTest extends TestCase
{
    public const DC_NS  = 'http://purl.org/dc/terms/';
    public const XML_NS = 'http://www.w3.org/XML/1998/namespace';
    public const XSD_NS = 'http://www.w3.org/2001/XMLSchema';
    public const XSI_NS = 'http://www.w3.org/2001/XMLSchema-instance';
    public const FOO_NS = 'http://foo.example.org';
    public const FOO2_NS = 'http://foo2.example.org';

    /**
     * @dataProvider getAttrsProvider
     */
    public function testGetAttrs($type, $expectedAttrs)
    {
        foreach ($type->getAttrs() as $name => $attr) {
            $this->assertTrue(
                $attr instanceof Attr || $attr instanceof PredefinedAttr
            );
            $this->assertSame($name, (string)$attr->getXName());
        }

        $this->assertSame($expectedAttrs, array_keys($type->getAttrs()));
    }

    public function getAttrsProvider()
    {
        $fooSchema = Schema::newFromDocument(
            Document::newFromUrl(
                'file:///' . dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR
                . 'foo.xml'
            )
        );

        return [
            // test complexContent/extension
            'xsd:schema' => [
                $fooSchema
                    ->getGlobalElement(new XName(self::XSD_NS, 'schema'))
                    ->getType(),
                [
                    self::XSI_NS . ' type',
                    'targetNamespace',
                    'version',
                    'finalDefault',
                    'blockDefault',
                    'attributeFormDefault',
                    'elementFormDefault',
                    'id',
                    self::XML_NS . ' lang'
                ]
            ],
            // test complexContent/extension including attribute group
            'xsd:attribute' => [
                $fooSchema
                    ->getGlobalType(new XName(self::XSD_NS, 'attribute')),
                [
                    self::XSI_NS . ' type',
                    'id',
                    'name',
                    'ref',
                    'type',
                    'use',
                    'default',
                    'fixed',
                    'form'
                ]
            ],
            // test complexContent/restriction and prohibition
            'xsd:topLevelAttribute' => [
                $fooSchema->getGlobalType(
                    new XName(self::XSD_NS, 'topLevelAttribute')
                ),
                [
                    self::XSI_NS . ' type',
                    'id',
                    'name',
                    'type',
                    'default',
                    'fixed'
                ]
            ],
            // test complexContent/extension
            'xsd:complexType' => [
                $fooSchema->getGlobalType(
                    new XName(self::XSD_NS, 'complexType')
                ),
                [
                    self::XSI_NS . ' type',
                    'id',
                    'name',
                    'mixed',
                    'abstract',
                    'final',
                    'block'
                ]
            ],
            // test complexContent/restriction and prohibition
            'xsd:localComplexType' => [
                $fooSchema->getGlobalType(
                    new XName(self::XSD_NS, 'localComplexType')
                ),
                [
                    self::XSI_NS . ' type',
                    'id',
                    'mixed'
                ]
            ],
            // test complexContent/extension including attribute groups
            'xsd:element' => [
                $fooSchema
                    ->getGlobalType(new XName(self::XSD_NS, 'element')),
                [
                    self::XSI_NS . ' type',
                    'id',
                    'name',
                    'ref',
                    'type',
                    'substitutionGroup',
                    'minOccurs',
                    'maxOccurs',
                    'default',
                    'fixed',
                    'nillable',
                    'abstract',
                    'final',
                    'block',
                    'form'
                ]
            ],
            // test complexContent/restriction and prohibition
            'xsd:topLevelElement' => [
                $fooSchema->getGlobalType(
                    new XName(self::XSD_NS, 'topLevelElement')
                ),
                [
                    self::XSI_NS . ' type',
                    'id',
                    'name',
                    'type',
                    'substitutionGroup',
                    'default',
                    'fixed',
                    'nillable',
                    'abstract',
                    'final',
                    'block'
                ]
            ],
            // test absence of complexContent
            'foo:foo' => [
                $fooSchema
                    ->getGlobalElement(new XName(self::FOO_NS, 'foo'))
                    ->getType(),
                [
                    self::XSI_NS . ' type',
                    self::XML_NS . ' lang',
                    self::DC_NS . ' source',
                    self::FOO_NS . ' quux',
                    'qux',
                    'bar',
                    'baz',
                    'foobar',
                    'barbaz',
                    'bazbaz',
                    'foofoofoo',
                    'barbarbar',
                    'bazbazbaz'
                ]
            ],
            // test simpleContent/extension
            'foo:foo' => [
                $fooSchema
                    ->getGlobalType(new XName(self::FOO_NS, 'Bar'))
                    ->getElements()[self::FOO_NS . ' baz']
                    ->getType(),
                [
                    self::XSI_NS . ' type',
                    self::XML_NS . ' id'
                ]
            ],
            // test simpleContent/restriction
            'foo:foo' => [
                $fooSchema
                    ->getGlobalType(new XName(self::FOO_NS, 'Qux')),
                [
                    self::XSI_NS . ' type',
                    self::XML_NS . ' lang',
                    'baz',
                    'minOccurs',
                    'maxOccurs'
                ]
            ]
        ];
    }

    /**
     * @dataProvider getElementsProvider
     */
    public function testGetElements($type, $expectedElementLocalNames)
    {
        $decls = $type->getElements();

        $this->assertSame(count($expectedElementLocalNames), count($decls));

        $i = 0;
        foreach ($decls as $decl) {
            $this->assertSame(
                $expectedElementLocalNames[$i++],
                $decl->getXName()->getLocalName()
            );
        }
    }

    public function getElementsProvider()
    {
        $fooSchema = Schema::newFromDocument(
            Document::newFromUrl(
                'file:///' . dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR
                . 'foo.xml'
            )
        );

        return [
            // test all cases except extension
            'xsd:simpleRestrictionType' => [
                $fooSchema->getGlobalType(
                    new XName(self::XSD_NS, 'simpleRestrictionType')
                ),
                [
                    'annotation',
                    'anyAttribute',
                    'attribute',
                    'attributeGroup',
                    'group',
                    'all',
                    'choice',
                    'sequence',
                    'simpleType',
                    'minExclusive',
                    'minInclusive',
                    'maxExclusive',
                    'maxInclusive',
                    'totalDigits',
                    'fractionDigits',
                    'length',
                    'minLength',
                    'maxLength',
                    'enumeration',
                    'whiteSpace',
                    'pattern'
                ]
            ],
            // test extension
            'xsd:simpleContent' => [
                $fooSchema->getGlobalElement(
                    new XName(self::XSD_NS, 'simpleContent')
                )->getType(),
                [
                    'annotation',
                    'restriction',
                    'extension'
                ]
            ]
        ];
    }
}
