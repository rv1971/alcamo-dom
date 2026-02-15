<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\{Schema, SchemaFactory};
use PHPUnit\Framework\TestCase;

class ListTypeTest extends TestCase
{
    public function test(): void
    {
        $schema = (new SchemaFactory())->getMainSchema();

        $type = $schema->getGlobalType(Schema::XSD_NS . ' IDREFS');

        $this->assertSame(
            $schema->getGlobalType(Schema::XSD_NS . ' IDREF'),
            $type->getItemType()
        );

        $this->assertNull($type->getHfpPropValue('bounded'));

        $this->assertFalse($type->isIntegral());
        $this->assertFalse($type->isNumeric());
    }
}
