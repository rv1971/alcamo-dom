<?php

namespace alcamo\dom;

use PHPUnit\Framework\TestCase;
use alcamo\exception\SyntaxError;

class ShallowDocumentTest extends TestCase
{
    public function testLoadUrl()
    {
        $fooDoc = ShallowDocument::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'foo.xml'
        );

        $this->assertNull($fooDoc->documentElement->firstChild);

        $this->assertSame(
            '123',
            $fooDoc->documentElement->getAttribute('qux:qux')
        );

        $barDoc = ShallowDocument::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'bar.xml'
        );

        $this->assertNull($barDoc->documentElement->firstChild);

        $this->assertSame(
            'bar-bar',
            $barDoc->documentElement->getAttribute('dc:identifier')
        );

        $quxDoc = ShallowDocument::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'qux.xml'
        );

        $this->assertNull($quxDoc->documentElement->firstChild);

        $this->assertSame('qux', $quxDoc->documentElement->localName);
    }
}
