<?php

namespace alcamo\dom\xsd;

use PHPUnit\Framework\TestCase;

class EnumeratorTest extends TestCase
{
    /**
     * @dataProvider toStringProvider
     */
    public function testToString($enum, $domNode, $expectedString)
    {
        $this->assertEquals($domNode, $enum->getDomNode());
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
                new Enumerator($qualified),
                $qualified,
                'qualified'
            ],
            [
                new Enumerator($unqualified),
                $unqualified,
                'unqualified'
            ]
        ];
    }
}
