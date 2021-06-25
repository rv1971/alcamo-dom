<?php

namespace alcamo\dom\xsd;

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

        return [
            [
                new Enumerator($doc->query('//*[@value = "qualified"]')[0]),
                'qualified'
            ],
            [
                new Enumerator($doc->query('//*[@value = "unqualified"]')[0]),
                'unqualified'
            ]
        ];
    }
}
