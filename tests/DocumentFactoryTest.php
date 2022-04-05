<?php

namespace alcamo\dom;

use GuzzleHttp\Psr7\UriResolver;
use PHPUnit\Framework\TestCase;
use alcamo\dom\decorated\Document as Xsd;
use alcamo\dom\psvi\Document as PsviDocument;
use alcamo\exception\{AbsoluteUriNeeded, ReadonlyViolation};
use alcamo\uri\Uri;

class MyDocumentFactory extends DocumentFactory
{
    public const X_NAME_TO_CLASS = [
        'https://corge.example.info corge' => __CLASS__
    ];
}

class DocumentFactoryTest extends TestCase
{
    /**
     * @dataProvider urlToClassProvider
     */
    public function testUrlToClass($url, $expectedClass)
    {
        $documentFactory = new MyDocumentFactory();

        $this->assertSame($expectedClass, $documentFactory->urlToClass($url));

        $this->assertSame(
            $expectedClass,
            $documentFactory->xmlTextToClass(file_get_contents($url))
        );
    }

    public function urlToClassProvider()
    {
        return [
            'xml' => [
                __DIR__ . DIRECTORY_SEPARATOR . 'foo.xml',
                Document::class
            ],
            'xsd' => [
                __DIR__ . DIRECTORY_SEPARATOR . 'foo.xsd',
                Xsd::class
            ],
            'corge' => [
                __DIR__ . DIRECTORY_SEPARATOR . 'corge.xml',
                MyDocumentFactory::class
            ]
        ];
    }

    /**
     * @dataProvider createClassProvider
     */
    public function testCreateClass($url, $class, $expectedClass)
    {
        $documentFactory = new MyDocumentFactory();

        $doc = $documentFactory->createFromUrl($url, $class);

        $this->assertInstanceOf($expectedClass, $doc);

        chdir(__DIR__);

        $doc2 = $documentFactory->createFromXmlText(
            file_get_contents($url),
            $class
        );

        $this->assertInstanceOf($expectedClass, $doc2);
    }

    public function createClassProvider()
    {
        return [
            'xml' => [
                __DIR__ . DIRECTORY_SEPARATOR . 'foo.xml',
                null,
                Document::class
            ],
            'xsd' => [
                __DIR__ . DIRECTORY_SEPARATOR . 'foo.xsd',
                null,
                Xsd::class
            ],
            'psvi' => [
                __DIR__ . DIRECTORY_SEPARATOR . 'foo.xml',
                PsviDocument::class,
                PsviDocument::class
            ]
        ];
    }

    public function testCache()
    {
        $baseUrl =
            'file://' . str_replace(DIRECTORY_SEPARATOR, '/', __DIR__) . '/';

        $documentFactory = new MyDocumentFactory($baseUrl);

        $this->assertEquals($baseUrl, $documentFactory->getBaseUrl());

        $barUrl = 'file://' . str_replace(DIRECTORY_SEPARATOR, '/', __DIR__)
            . '/bar.xml';

        $bar = $documentFactory->createFromUrl('bar.xml');

        $this->assertEquals($barUrl, $bar->documentURI);

        $bar->documentElement->setAttribute('foo', 'FOO');

        $this->assertSame('FOO', $bar->documentElement->getAttribute('foo'));

        // $bar2 does not use the cache, so it does not see the change to $bar
        $bar2Url = 'extended/../bar.xml';

        $bar2 = $documentFactory->createFromUrl($bar2Url, null, null, false);

        $this->assertFalse($bar2->documentElement->hasAttribute('foo'));

        // $bar3 uses the cache, so so it sees the change
        $bar3Url = 'xsd/../bar.xml';

        $bar3 = $documentFactory->createFromUrl($bar3Url);

        $this->assertSame($bar, $bar3);
        $this->assertSame('FOO', $bar3->documentElement->getAttribute('foo'));

        $baz = $documentFactory->createFromUrl('baz.xml');

        $baz->documentElement->setAttribute('baz', 'BAZ');

        $this->assertSame('BAZ', $baz->documentElement->getAttribute('baz'));

        MyDocumentFactory::addToCache($baz);

        // $baz2 sees the change in the cached document
        $baz2 = $documentFactory->createFromUrl('baz.xml');

        $this->assertSame($baz, $baz2);
        $this->assertSame('BAZ', $baz2->documentElement->getAttribute('baz'));
    }

    public function testCacheException()
    {
        $documentFactory = new MyDocumentFactory();

        $this->expectException(AbsoluteUriNeeded::class);
        $this->expectExceptionMessage(
            'Relative URI <alcamo\uri\Uri>"'
            . __DIR__ . DIRECTORY_SEPARATOR
            . 'bar.xml" given where absolute URI is needed'
        );

        $documentFactory->createFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'bar.xml',
            null,
            null,
            true
        );
    }

    public function testAddToCacheException()
    {
        $documentFactory = new MyDocumentFactory();

        $barUrl = 'file://' . str_replace(DIRECTORY_SEPARATOR, '/', __DIR__)
            . DIRECTORY_SEPARATOR . 'bar.xml';

        $bar1 = $documentFactory->createFromUrl($barUrl);

        $bar2 = $documentFactory->createFromUrl($barUrl, null, null, false);

        MyDocumentFactory::addToCache($bar1);

        $this->expectException(ReadonlyViolation::class);
        $this->expectExceptionMessage(
            'Attempt to modify readonly object '
            . '"alcamo\dom\DocumentFactory cache"; '
            . 'attempt to replace cache entry "file:///'
        );

        MyDocumentFactory::addToCache($bar2);
    }
}
