<?php

namespace alcamo\dom;

use PHPUnit\Framework\TestCase;
use alcamo\uri\Uri;

class DocumentsFactoryTest extends TestCase
{
    public function testNewFromGlob()
    {
        $factory = new DocumentsFactory();

        $docs =
            $factory->createFromGlob(__DIR__ . DIRECTORY_SEPARATOR . '*.xml');

        $this->assertSame('foo', $docs['foo.xml']->documentElement->tagName);

        $this->assertSame('bar', $docs['bar-bar']->documentElement->tagName);

        $this->assertSame('schema', $docs['baz.xml']->documentElement->tagName);
    }

    public function testNewFromUrls()
    {
        $factory = new DocumentsFactory(
            new DocumentFactory(
                'file://'
                    . str_replace(__DIR__ . DIRECTORY_SEPARATOR, '/', __DIR__)
                    . '/'
            )
        );

        $docs = $factory->createFromUrls(
            [ 'foo.xml', new Uri('bar.xml'), 'BAZ' => 'baz.xml' ]
        );

        $this->assertSame('foo', $docs['foo.xml']->documentElement->tagName);

        $this->assertSame('bar', $docs['bar-bar']->documentElement->tagName);

        $this->assertSame('schema', $docs['BAZ']->documentElement->tagName);
    }
}
