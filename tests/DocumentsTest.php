<?php

namespace alcamo\dom;

use PHPUnit\Framework\TestCase;
use alcamo\exception\Uninitialized;
use alcamo\ietf\Uri;

class DocumentsTest extends TestCase
{
    public function textConstruct()
    {
        $docs = new Documents([
            'FOO'
            => Document::newFromUrl(__DIR__ . DIRECTORY_SEPARATOR . 'foo.xml'),
            Document::newFromUrl(__DIR__ . DIRECTORY_SEPARATOR . 'bar.xml')
        ]);

        $this->assertSame('foo', $docs['FOO']->documentElement->tagName);

        $this->assertSame('bar', $docs['bar-bar']->documentElement->tagName);
    }

    public function testNewFromGlob()
    {
        $docs =
            Documents::newFromGlob(__DIR__ . DIRECTORY_SEPARATOR . '*.xml');

        $this->assertSame('foo', $docs['foo']->documentElement->tagName);

        $this->assertSame('bar', $docs['bar-bar']->documentElement->tagName);
    }

    public function testNewFromUrls()
    {
        $docs = Documents::newFromUrls(
            [ 'foo.xml', new Uri('bar.xml') ],
            'file://' . str_replace(DIRECTORY_SEPARATOR, '/', __DIR__) . '/'
        );

        $this->assertSame('foo', $docs['foo']->documentElement->tagName);

        $this->assertSame('bar', $docs['bar-bar']->documentElement->tagName);
    }

    public function testNewFromUrlsAbs()
    {
        $docs = Documents::newFromUrls(
            [
                new Uri(
                    'file://' . str_replace(DIRECTORY_SEPARATOR, '/', __DIR__)
                    . '/foo.xml'
                ),
                'file://' . str_replace(DIRECTORY_SEPARATOR, '/', __DIR__)
                . '/bar.xml'
            ]
        );

        $this->assertSame('foo', $docs['foo']->documentElement->tagName);

        $this->assertSame('bar', $docs['bar-bar']->documentElement->tagName);
    }
}
