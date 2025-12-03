<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\Schema;
use PHPUnit\Framework\TestCase;

class AbstractSimpleTypeTest extends TestCase
{
    public function testNewFromSchemaAndXsdElement(): void
    {
        $schema = Schema::getBuiltinSchema();

        $formChoiceType =
            $schema->getGlobalType(Schema::XSD_NS . ' formChoice');

        $this->assertInstanceof(EnumerationType::class, $formChoiceType);

        /* All other cases of AbstractSimpleType::newFromSchemaAndXsdElement()
         * are already covered in the tests of the respective simple types. */
    }
}
