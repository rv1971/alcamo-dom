<?php

namespace alcamo\dom\schema\component;

use PHPUnit\Framework\TestCase;
use alcamo\dom\Document;
use alcamo\dom\schema\Schema;
use alcamo\xml\XName;

class AbstractSimpleTypeTest extends TestCase
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
                self::XSD_NS . ' nonNegativeInteger',
                self::XSD_NS . ' nonNegativeInteger',
                true
            ],
            [
                $schema,
                self::XSD_NS . ' nonNegativeInteger',
                self::XSD_NS . ' integer',
                true
            ],
            [
                $schema,
                self::XSD_NS . ' unsignedLong',
                self::XSD_NS . ' nonNegativeInteger',
                true
            ],
            [
                $schema,
                self::XSD_NS . ' unsignedLong',
                self::XSD_NS . ' integer',
                true
            ],
            [
                $schema,
                self::XSD_NS . ' long',
                self::XSD_NS . ' decimal',
                true
            ],
            [
                $schema,
                self::XSD_NS . ' int',
                self::XSD_NS . ' long',
                true
            ],
            [
                $schema,
                self::XSD_NS . ' long',
                self::XSD_NS . ' int',
                false
            ],
            [
                $schema,
                self::XSD_NS . ' long',
                self::XSD_NS . ' string',
                false
            ],
            [
                $schema,
                self::XSD_NS . ' normalizedString',
                self::XSD_NS . ' string',
                true
            ]
        ];
    }
}
