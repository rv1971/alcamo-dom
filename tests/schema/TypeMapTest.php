<?php

namespace alcamo\dom\schema;

use PHPUnit\Framework\TestCase;
use alcamo\dom\extended\Document;
use alcamo\exception\Locked;
use alcamo\xml\XName;

class TypeMapTest extends TestCase
{
    public const XML_NS = 'http://www.w3.org/XML/1998/namespace';
    public const XSD_NS = 'http://www.w3.org/2001/XMLSchema';
    public const XSI_NS = 'http://www.w3.org/2001/XMLSchema-instance';
    public const FOO_NS = 'http://foo.example.org';
    public const FOO2_NS = 'http://foo2.example.org';

    /**
     * @dataProvider lookupProvider
     */
    public function testLookup($map, $type, $expected, $expectedMapSize)
    {
        $this->assertSame($expected, $map->lookup($type));
        $this->assertSame($expectedMapSize, count($map->getMap()));
    }

    public function lookupProvider()
    {
        $schema = Schema::newFromDocument(
            Document::newFromUrl(
                'file:///' . dirname(__DIR__) . DIRECTORY_SEPARATOR
                . 'foo.xml'
            )
        );

        $origMap = [
            self::XSD_NS . ' openAttrs' => 'OPENATTRS',
            self::XSD_NS . ' attribute' => 'ATTRIBUTE'
        ];

        $map1 = TypeMap::newFromSchemaAndXNameMap($schema, $origMap);

        $map2 = TypeMap::newFromSchemaAndXNameMap($schema, $origMap, 'NONE');

        return [
            'exact-match-1' => [
                $map1,
                $schema->getGlobalType(self::XSD_NS . ' openAttrs'),
                'OPENATTRS',
                count($origMap)
            ],
            'base-match-1' => [
                $map1,
                $schema->getGlobalType(self::XSD_NS . ' annotated'),
                'OPENATTRS',
                count($origMap) + 1
            ],
            'exact-match-2' => [
                $map1,
                $schema->getGlobalType(self::XSD_NS . ' attribute'),
                'ATTRIBUTE',
                count($origMap) + 1
            ],
            'base-match-2' => [
                $map1,
                $schema->getGlobalType(self::XSD_NS . ' topLevelAttribute'),
                'ATTRIBUTE',
                count($origMap) + 2
            ],
            'base-match-3' => [
                $map1,
                $schema->getGlobalType(self::XSD_NS . ' complexType'),
                'OPENATTRS',
                count($origMap) + 3
            ],
            'base-match-3-x' => [
                $map1,
                $schema->getGlobalType(self::XSD_NS . ' complexType'),
                'OPENATTRS',
                count($origMap) + 3
            ],
            'no-match' => [
                $map1,
                $schema->getGlobalType(self::XSD_NS . ' anyType'),
                null,
                count($origMap) + 4
            ],
            'no-match-x' => [
                $map1,
                $schema->getGlobalType(self::XSD_NS . ' anyType'),
                null,
                count($origMap) + 4
            ],
            'default-match' => [
                $map2,
                $schema->getGlobalType(self::XSD_NS . ' anyType'),
                'NONE',
                count($origMap) + 1
            ],
            'default-match-x' => [
                $map2,
                $schema->getGlobalType(self::XSD_NS . ' anyType'),
                'NONE',
                count($origMap) + 1
            ]
        ];
    }

    public function testAddItems()
    {
        $schema = Schema::newFromDocument(
            Document::newFromUrl(
                'file:///' . dirname(__DIR__) . DIRECTORY_SEPARATOR
                . 'foo.xml'
            )
        );

        $origMap = [
            self::XSD_NS . ' complexType' => 'COMPLEXTYPE',
            self::XSD_NS . ' topLevelComplexType' => 'TOPLEVELCOMPLEXTYPE'
        ];

        $map = TypeMap::newFromSchemaAndXNameMap($schema, $origMap);

        $this->assertSame(
            'COMPLEXTYPE',
            $map->lookup($schema->getGlobalType(self::XSD_NS . ' complexType'))
        );

        $this->assertSame(
            'TOPLEVELCOMPLEXTYPE',
            $map->lookup(
                $schema->getGlobalType(self::XSD_NS . ' topLevelComplexType')
            )
        );

        $this->assertSame(2, count($map->getMap()));

        $map->addItems(TypeMap::createHashMapFromSchemaAndXNameMap(
            $schema,
            [  self::XSD_NS . ' localComplexType' => 'LOCALCOMPLEXTYPE' ]
        ));

        $this->assertSame(
            'LOCALCOMPLEXTYPE',
            $map->lookup(
                $schema->getGlobalType(self::XSD_NS . ' localComplexType')
            )
        );

        $this->assertSame(3, count($map->getMap()));

        $this->assertSame(
            'LOCALCOMPLEXTYPE',
            $map->lookup(
                $schema->getGlobalType(self::FOO_NS . ' fooComplexType')
            )
        );

        $this->assertSame(4, count($map->getMap()));
    }

    public function testAddItemsException()
    {
        $schema = Schema::newFromDocument(
            Document::newFromUrl(
                'file:///' . dirname(__DIR__) . DIRECTORY_SEPARATOR
                . 'foo.xml'
            )
        );

        $origMap = [
            self::XSD_NS . ' complexType' => 'COMPLEXTYPE'
        ];

        $map = TypeMap::newFromSchemaAndXNameMap($schema, $origMap, 'NONE');

        $this->assertSame(
            'COMPLEXTYPE',
            $map->lookup($schema->getGlobalType(self::XSD_NS . ' complexType'))
        );

        $map->addItems(TypeMap::createHashMapFromSchemaAndXNameMap(
            $schema,
            [
                self::XSD_NS . ' complexType' => 'COMPLEXTYPE2',
                self::XSD_NS . ' localComplexType' => 'LOCALCOMPLEXTYPE'
            ]
        ));

        $this->assertSame(
            'COMPLEXTYPE',
            $map->lookup($schema->getGlobalType(self::XSD_NS . ' complexType'))
        );

        $this->assertSame(2, count($map->getMap()));

        $this->assertSame(
            'COMPLEXTYPE',
            $map->lookup(
                $schema->getGlobalType(self::XSD_NS . ' topLevelComplexType')
            )
        );

        $this->assertSame(3, count($map->getMap()));

        $this->expectException(Locked::class);
        $this->expectExceptionMessage(
            'Attempt to modify locked ' . TypeMap::class
        );

        $map->addItems([]);
    }

    public function testReplaceItems()
    {
        $schema = Schema::newFromDocument(
            Document::newFromUrl(
                'file:///' . dirname(__DIR__) . DIRECTORY_SEPARATOR
                . 'foo.xml'
            )
        );

        $origMap = [
            self::XSD_NS . ' complexType' => 'COMPLEXTYPE'
        ];

        $map = TypeMap::newFromSchemaAndXNameMap($schema, $origMap);

        $this->assertSame(
            'COMPLEXTYPE',
            $map->lookup($schema->getGlobalType(self::XSD_NS . ' complexType'))
        );

        $this->assertSame(1, count($map->getMap()));

        $map->replaceItems(TypeMap::createHashMapFromSchemaAndXNameMap(
            $schema,
            [
                self::XSD_NS . ' complexType' => 'COMPLEXTYPE2',
                self::XSD_NS . ' localComplexType' => 'LOCALCOMPLEXTYPE'
            ]
        ));

        $this->assertSame(
            'LOCALCOMPLEXTYPE',
            $map->lookup(
                $schema->getGlobalType(self::XSD_NS . ' localComplexType')
            )
        );

        $this->assertSame(
            'COMPLEXTYPE2',
            $map->lookup($schema->getGlobalType(self::XSD_NS . ' complexType'))
        );

        $this->assertSame(
            'COMPLEXTYPE2',
            $map->lookup(
                $schema->getGlobalType(self::XSD_NS . ' topLevelComplexType')
            )
        );

        $this->assertSame(3, count($map->getMap()));
    }

    public function testReplaceItemsException()
    {
        $schema = Schema::newFromDocument(
            Document::newFromUrl(
                'file:///' . dirname(__DIR__) . DIRECTORY_SEPARATOR
                . 'foo.xml'
            )
        );

        $origMap = [
            self::XSD_NS . ' complexType' => 'COMPLEXTYPE'
        ];

        $map = TypeMap::newFromSchemaAndXNameMap($schema, $origMap, 'NONE');

        $this->assertSame(
            'COMPLEXTYPE',
            $map->lookup($schema->getGlobalType(self::XSD_NS . ' complexType'))
        );

        $this->assertSame(
            'NONE',
            $map->lookup(
                $schema->getGlobalType(self::XSD_NS . ' anyType')
            )
        );

        $this->assertSame(2, count($map->getMap()));


        $this->expectException(Locked::class);
        $this->expectExceptionMessage(
            'Attempt to modify locked ' . TypeMap::class
        );

        $map->replaceItems([]);
    }
}
