<?php

namespace alcamo\dom;

use alcamo\exception\InvalidType;
use PHPUnit\Framework\TestCase;

class DocumentsTest extends TestCase
{
    public function testConstruct(): void
    {
        $docs = new Documents(
            [
                'FOO' => Document::newFromUrl(
                    __DIR__ . DIRECTORY_SEPARATOR . 'foo.xml'
                ),
                Document::newFromUrl(__DIR__ . DIRECTORY_SEPARATOR . 'bar.xml'),
                Document::newFromUrl(__DIR__ . DIRECTORY_SEPARATOR . 'baz.xml')
            ]
        );

        $this->assertSame('foo', $docs['FOO']->documentElement->tagName);

        $this->assertSame('bar', $docs['bar-bar']->documentElement->tagName);

        $this->assertSame('schema', $docs['baz.xml']->documentElement->tagName);
    }

    public function testConstructException(): void
    {
        $this->expectException(InvalidType::class);
        $this->expectExceptionMessage(
            'Invalid type "string", expected one of ["alcamo\dom\Document"] for key 0'
        );

        new Documents([ 'Lorem ipsum.' ]);
    }
}
