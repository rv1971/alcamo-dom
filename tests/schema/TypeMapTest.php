<?php

namespace alcamo\dom\schema;

use alcamo\dom\Document;
use alcamo\exception\Locked;
use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class TypeMapTest extends TestCase
{
    public const BASE_MAP = [
        Schema::XSD_NS . ' decimal' => 'DECIMAL',
        Schema::XSD_NS . ' short'   => 'SHORT',
        Schema::XSD_NS . ' string'  => '---'
    ];

    public const ADD_MAP = [
        Schema::XSD_NS . ' decimal' => '---',
        Schema::XSD_NS . ' NMTOKENS'    => 'NMTOKENS'
    ];

    public const REPLACE_MAP = [
        Schema::XSD_NS . ' string' => 'STRING',
        Schema::XSD_NS . ' date'   => 'DATE'
    ];

    public function testLookup(): void
    {
        $schema = (new SchemaFactory())->getBuiltinSchema();

        $map = TypeMap::newFromSchemaAndXNameMap($schema, self::BASE_MAP, '*');

        $this->assertSame(3, count($map->getMap()));

        $this->assertSame('*', $map->getDefaultValue());

        $map->addItems(
            TypeMap::newFromSchemaAndXNameMap($schema, self::ADD_MAP)->getMap()
        );

        $this->assertSame(4, count($map->getMap()));

        $map->replaceItems(
            TypeMap::newFromSchemaAndXNameMap($schema, self::REPLACE_MAP)
                ->getMap()
        );

        $this->assertSame(5, count($map->getMap()));

        $this->assertSame(
            'DECIMAL',
            $map->lookup($schema->getGlobalType(Schema::XSD_NS . ' long'))
        );

        $this->assertSame(7, count($map->getMap()));

        $this->assertSame(
            'SHORT',
            $map->lookup($schema->getGlobalType(Schema::XSD_NS . ' short'))
        );

        $this->assertSame(7, count($map->getMap()));

        $this->assertSame(
            'SHORT',
            $map->lookup($schema->getGlobalType(Schema::XSD_NS . ' byte'))
        );

        $this->assertSame(8, count($map->getMap()));

        $this->assertSame(
            'STRING',
            $map->lookup($schema->getGlobalType(Schema::XSD_NS . ' token'))
        );

        $this->assertSame(10, count($map->getMap()));

        $this->assertSame(
            'DATE',
            $map->lookup($schema->getGlobalType(Schema::XSD_NS . ' date'))
        );

        $this->assertSame(10, count($map->getMap()));

        $this->assertSame(
            'NMTOKENS',
            $map->lookup($schema->getGlobalType(Schema::XSD_NS . ' NMTOKENS'))
        );

        $this->assertSame(10, count($map->getMap()));
    }

    public function testExceptionInAddItems(): void
    {
        $schema = (new SchemaFactory())->getBuiltinSchema();

        $map = TypeMap::newFromSchemaAndXNameMap($schema, self::BASE_MAP);

        $this->assertSame(
            'DECIMAL',
            $map->lookup($schema->getGlobalType(Schema::XSD_NS . ' decimal'))
        );

        $map->addItems(
            TypeMap::newFromSchemaAndXNameMap($schema, self::ADD_MAP)->getMap()
        );

        $this->assertSame(
            'DECIMAL',
            $map->lookup(
                $schema->getGlobalType(Schema::XSD_NS . ' unsignedByte')
            )
        );

        $this->expectException(Locked::class);

        $map->addItems([]);
    }

    public function testExceptionInReplaceItems(): void
    {
        $schema = (new SchemaFactory())->getBuiltinSchema();

        $map = TypeMap::newFromSchemaAndXNameMap($schema, self::BASE_MAP);

        $this->assertSame(
            'DECIMAL',
            $map->lookup($schema->getGlobalType(Schema::XSD_NS . ' decimal'))
        );

        $map->addItems(
            TypeMap::newFromSchemaAndXNameMap($schema, self::ADD_MAP)->getMap()
        );

        $this->assertSame(
            'DECIMAL',
            $map->lookup(
                $schema->getGlobalType(Schema::XSD_NS . ' unsignedByte')
            )
        );

        $this->expectException(Locked::class);

        $map->replaceItems([]);
    }
}
