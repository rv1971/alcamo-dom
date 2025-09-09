<?php

namespace alcamo\dom\xsd;

use alcamo\dom\decorated\Document;
use PHPUnit\Framework\TestCase;

class EnumeratorTest extends TestCase
{
    /**
     * @dataProvider toStringProvider
     */
    public function testToString($enum, $expectedString)
    {
        $this->assertEquals($expectedString, (string)$enum);
    }

    public function toStringProvider()
    {
        $doc = Document::newFromUrl(
            dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR
            . 'xsd' . DIRECTORY_SEPARATOR . 'XMLSchema.xsd'
        )->conserve();

        $qualified = $doc->query('//*[@value = "qualified"]')[0];
        $unqualified = $doc->query('//*[@value = "unqualified"]')[0];

        return [
            [
                $qualified,
                'qualified'
            ],
            [
                $unqualified,
                'unqualified'
            ]
        ];
    }
}
