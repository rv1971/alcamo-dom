<?php

namespace alcamo\dom\schema\component;

use PHPUnit\Framework\TestCase;
use alcamo\dom\extended\Document;
use alcamo\dom\schema\Schema;
use alcamo\xml\XName;

class GroupTest extends TestCase
{
    public const XML_NS = 'http://www.w3.org/XML/1998/namespace';
    public const XSD_NS = 'http://www.w3.org/2001/XMLSchema';
    public const FOO_NS = 'http://foo.example.org';
    public const FOO2_NS = 'http://foo2.example.org';

    /**
     * @dataProvider getElementsProvider
     */
    public function testGetElements($group, $expectedElementLocalNames)
    {
        $decls = $group->getElements();

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
            // test sequence and sub-group
            'simpleRestrictionModel' => [
                $fooSchema->getGlobalGroup(
                    new XName(self::XSD_NS, 'simpleRestrictionModel')
                ),
                [
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
            // test choice and sub-group
            'schemaTop' => [
                $fooSchema->getGlobalGroup(
                    new XName(self::XSD_NS, 'schemaTop')
                ),
                [
                    'simpleType',
                    'complexType',
                    'group',
                    'attributeGroup',
                    'element',
                    'attribute',
                    'notation'
                ]
            ],
            // test nesting
            'attrDecls' => [
                $fooSchema->getGlobalGroup(
                    new XName(self::XSD_NS, 'attrDecls')
                ),
                [
                    'anyAttribute',
                    'attribute',
                    'attributeGroup'
                ]
            ],
            // test nesting with sub-groups
            'complexTypeModel' => [
                $fooSchema->getGlobalGroup(
                    new XName(self::XSD_NS, 'complexTypeModel')
                ),
                [
                    'simpleContent',
                    'complexContent',
                    'group',
                    'all',
                    'choice',
                    'sequence',
                    'anyAttribute',
                    'attribute',
                    'attributeGroup'
                ]
            ]
        ];
    }
}
