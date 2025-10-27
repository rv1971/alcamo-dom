<?php

namespace alcamo\dom;

use alcamo\xml\XName;
use PHPUnit\Framework\TestCase;

class ChildElementsByAttrIteratorTest extends TestCase
{
    /**
     * @dataProvider iterationProvider
     */
    public function testIteration(
        $parentElement,
        $attrName,
        $contentAttr,
        $expectedData
    ): void {
        $iterator = new ChildElementsByAttrIterator($parentElement, $attrName);

        reset($expectedData);

        foreach ($iterator as $id => $element) {
            $this->assertSame(key($expectedData), $id);
            $this->assertSame(
                current($expectedData),
                $element->getAttribute($contentAttr)
            );

            next($expectedData);
        }
    }

    public function iterationProvider(): array
    {
        $foo = Document::newFromUrl(__DIR__ . DIRECTORY_SEPARATOR . 'foo.xml');

        return [
            [
                $foo['x'],
                new XName(Document::XML_NS, 'id'),
                'content',
                [
                    'a' => '',
                    'b' => '',
                    'c' => '',
                    'd' => '',
                    'datetime' => '2021-02-16T18:04:03.123+00:00',
                    'duration' => 'PT5M',
                    'float' => '3.141',
                    'intset' => '42 -42 0 7 5',
                    'lang' => 'yo-NG',
                    'media-type' => 'application/json',
                    'longint' => '123456789012345678901234567890',
                    'ref' => 'lang',
                    'bool-1' => 'yes',
                    'bool-0' => 'no',
                    'base64' => 'Zm9vCg==',
                    'hex' => '1234abcdef',
                    'pairsToMap' => 'foo bar baz-qux 42',
                    'curie' => 'dc:source',
                    'safecurie' => '[qux:#1234]',
                    'uriorsafecurie1' => 'http://www.example.biz/foo',
                    'uriorsafecurie2' => '[xsd:#token]',
                    'document' => 'foo.xml',
                    'xpointer1' => 'foo.xml#b',
                    'xpointer2' => "#xpointer(//@xml:id[starts-with^(., 'd'^)])",
                    '' => ''
                ]
            ],
            [
                $foo,
                'bar',
                'baz',
                [ 'true' => 'false' ]
            ]
        ];
    }
}
