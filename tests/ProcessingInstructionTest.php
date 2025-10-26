<?php

namespace alcamo\dom;

use PHPUnit\Framework\TestCase;

class ProcessingInstructionTest extends TestCase
{
    /**
     * @dataProvider basicsProvider
     */
    public function testBasics(
        $pi,
        $expectedRfc5147Fragment,
        $expectedAttributes
    ): void {
        $this->assertSame($expectedRfc5147Fragment, $pi->getRfc5147Fragment());

        $this->assertSame(
            count($expectedAttributes),
            count($pi->getAttributes())
        );

        foreach ($pi as $name => $value) {
            $this->assertSame($expectedAttributes[$name], (string)$value);
            $this->assertSame($expectedAttributes[$name], $pi->$name);
        }
    }

    public function basicsProvider(): array
    {
        $foo =
            Document::newFromUrl(__DIR__ . DIRECTORY_SEPARATOR . 'foo.xml');

        return [
            [
                $foo->query("/processing-instruction()")[0],
                'line=2,3',
                [ 'href' => 'foo.xsl', 'type' => 'text/xsl' ]
            ]
        ];
    }
}
