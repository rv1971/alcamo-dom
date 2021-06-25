<?php

namespace alcamo\dom;

use GuzzleHttp\Psr7\UriResolver;
use PHPUnit\Framework\TestCase;
use alcamo\dom\xsd\Document as Xsd;
use alcamo\dom\psvi\Document as PsviDocument;
use alcamo\exception\{AbsoluteUriNeeded, Locked};
use alcamo\ietf\Uri;

class DocumentFactoryTest extends TestCase
{
    /**
     * @dataProvider urlToClassProvider
     */
    public function testUrlToClass($url, $expectedClass)
    {
        $documentFactory = new DocumentFactory();

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
            ]
        ];
    }

    /**
     * @dataProvider createClassProvider
     */
    public function testCreateClass($url, $class, $expectedClass)
    {
        $documentFactory = new DocumentFactory();

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

        $documentFactory = new DocumentFactory($baseUrl);

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

        DocumentFactory::addToCache($baz);

        // $baz2 sees the change in the cached document
        $baz2 = $documentFactory->createFromUrl('baz.xml');

        $this->assertSame($baz, $baz2);
        $this->assertSame('BAZ', $baz2->documentElement->getAttribute('baz'));
    }

    public function testCacheException()
    {
        $documentFactory = new DocumentFactory();

        $this->expectException(AbsoluteUriNeeded::class);
        $this->expectExceptionMessage(
            'Relative URI "'
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
        $documentFactory = new DocumentFactory();

        $barUrl = 'file://' . str_replace(DIRECTORY_SEPARATOR, '/', __DIR__)
            . DIRECTORY_SEPARATOR . 'bar.xml';

        $bar1 = $documentFactory->createFromUrl($barUrl);

        $bar2 = $documentFactory->createFromUrl($barUrl, null, null, false);

        DocumentFactory::addToCache($bar1);

        $this->expectException(Locked::class);
        $this->expectExceptionMessage(
            "Attempt to replace cache entry \"$barUrl\" with a different document"
        );

        DocumentFactory::addToCache($bar2);
    }
}
