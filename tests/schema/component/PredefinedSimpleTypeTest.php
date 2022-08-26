<?php

namespace alcamo\dom\schema\component;

use PHPUnit\Framework\TestCase;
use alcamo\dom\Document;
use alcamo\dom\schema\Schema;
use alcamo\xml\XName;

class PredefinedSimpleTypeTest extends TestCase
{
    public const XSD_NS = Document::XSD_NS;

    /**
     * @dataProvider isDerivedFromProvider
     */
    public function testIsDerivedFrom(
        $schema,
        $xName,
        $baseXName,
        $expectedResult
    ): void {
        $this->assertSame(
            $expectedResult,
            $schema->getGlobalType($xName)->isEqualToOrDerivedFrom($baseXName)
        );
    }

    public function isDerivedFromProvider()
    {
        $schema = Schema::newFromUrls(
            [
                'file://' . dirname(dirname(dirname(__DIR__)))
                . '/xsd/XMLSchema.xsd'
            ]
        );

        return [
            [
                $schema,
                self::XSD_NS . ' anySimpleType',
                self::XSD_NS . ' anySimpleType',
                true
            ],
            [
                $schema,
                self::XSD_NS . ' anySimpleType',
                self::XSD_NS . ' anyType',
                true
            ],
            [
                $schema,
                self::XSD_NS . ' anySimpleType',
                self::XSD_NS . ' string',
                false
            ]
        ];
    }
}
