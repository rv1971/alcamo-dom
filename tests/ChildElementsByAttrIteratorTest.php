<?php

namespace alcamo\dom;

use PHPUnit\Framework\TestCase;

class ChildElementsByAttrIteratorTest extends TestCase
{
    public function testIteration()
    {
        $doc = Document::newFromUrl(__DIR__ . DIRECTORY_SEPARATOR . 'foo.xml');

        $iterator = new ChildElementsByAttrIterator($doc['x'], 'xml:id');

        $expectedResult = [
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
        ];

        reset($expectedResult);

        foreach ($iterator as $id => $element) {
            $this->assertSame(key($expectedResult), $id);
            $this->assertSame(
                current($expectedResult),
                $element->getAttribute('content')
            );

            next($expectedResult);
        }
    }
}
