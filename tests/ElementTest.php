<?php

namespace alcamo\dom;

use alcamo\exception\AbsoluteUriNeeded;
use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class ElementTest extends TestCase
{
    public const DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR;

    /**
     * @dataProvider getMetaDataProvider
     */
    public function testGetMetaData(
        $element,
        $expectedBaseUri,
        $expectedResolvedUri,
        $expectedXName,
        $expectedRfc5147Fragment,
        $expectedRfc5147Uri
    ): void {
        /* This also tests the traits HavingBaseUriTrait, HavingXNameTrait,
         * Rfc5147Trait. */

        $this->assertSame($element->textContent, (string)$element);

        $this->assertSame($element->textContent, $element->getValue());

        $this->assertSame(
            (string)$expectedBaseUri,
            (string)$element->getBaseUri()
        );

        $this->assertSame(
            (string)$expectedResolvedUri,
            (string)$element->resolveUri('README.md')
        );

        $this->assertSame(
            $expectedXName,
            (string)$element->getXName()
        );

        $this->assertSame(
            $expectedRfc5147Fragment,
            $element->getRfc5147Fragment()
        );

        $this->assertSame(
            $expectedRfc5147Uri,
            $element->getRfc5147Uri()
        );
    }

    public function getMetaDataProvider(): array
    {
        $fileUriFactory = new FileUriFactory(null, false);

        $fooDoc = Document::newFromPathname(self::DATA_DIR . 'foo.xml');

        return [
            [
                $fooDoc->documentElement,
                $fooDoc->documentURI,
                $fileUriFactory->create(self::DATA_DIR . 'README.md'),
                'http://foo.example.org foo',
                'line=9',
                $fooDoc->documentURI . '#line=9'
            ],
            [
                $fooDoc['bar'],
                'http://bar.example.biz',
                'http://bar.example.biz/README.md',
                'https://bar.example.com bar',
                'line=14',
                $fooDoc->documentURI . '#line=14'
            ],
            [
                $fooDoc['bar']->firstChild,
                'http://bar.example.biz',
                'http://bar.example.biz/README.md',
                'https://bar.example.com baz',
                'line=15',
                $fooDoc->documentURI . '#line=15'
            ]
        ];
    }

    public function testGetIterator(): void
    {
        /* This also tests class ChildElementsIterator. */

        $fooDoc = Document::newFromPathname(self::DATA_DIR . 'foo.xml');

        $data = [];

        foreach ($fooDoc['bar'] as $i => $text) {
            $data[$i] = (string)$text;
        }

        $this->assertSame(
            [
                0 => 'Lorem ipsum',
                1 => 'dolor sit amet',
                2 => 'consetetur sadipscing elitr'
            ],
            $data
        );
    }

    /**
     * @dataProvider queryProvider
     */
    public function testQuery($xPath, $expectedText): void
    {
        /* This also tests the class XPath. */

        $fooDoc = Document::newFromPathname(self::DATA_DIR . 'foo.xml');

        $element = $fooDoc->documentElement->query($xPath)[0];

        $this->assertSame($expectedText, (string)$element);
    }

    public function queryProvider(): array
    {
        return [
            [ '//*[@owl:sameAs][2]', 'dolor sit amet' ],
            [ '*//xh:b', 'sadipscing' ]
        ];
    }

    /**
     * @dataProvider evaluateProvider
     */
    public function testEvaluate($xPath, $expectedResult): void
    {
        /* This also tests the class XPath. */

        $fooDoc = Document::newFromPathname(self::DATA_DIR . 'foo.xml');

        $fooDoc->getXPath()->registerPhpFunctions();

        $result = $fooDoc->documentElement->evaluate($xPath);

        $this->assertSame($expectedResult, $result);
    }

    public function evaluateProvider(): array
    {
        return [
            [ 'count(id("bar")/*)', 3.0 ],
            [ 'php:functionString("strtoupper", *//xh:b)', 'SADIPSCING' ]
        ];
    }

    /**
     * @dataProvider getFirstSameAsProvider
     */
    public function testGetFirstSameAs($uri, $expectedText): void
    {
        $fooDoc = Document::newFromPathname(self::DATA_DIR . 'foo.xml');

        $this->assertSame(
            $expectedText,
            (string)$fooDoc->documentElement->getFirstSameAs($uri)
        );
    }

    public function getFirstSameAsProvider(): array
    {
        return [
            [ 'http://bar.example.biz#a', 'Lorem ipsum' ],
            [ 'http://bar.example.biz#b', 'dolor sit amet' ],
            [ 'http://baz.example.edu#s', 'sadipscing' ]
        ];
    }

    public function testGetFirstSameAsException(): void
    {
        $fooDoc = Document::newFromPathname(self::DATA_DIR . 'foo.xml');

        $this->expectException(AbsoluteUriNeeded::class);
        $this->expectExceptionMessage(
            'Relative URI <alcamo\uri\Uri>"foo" given '
                . 'where absolute URI is needed'
        );

        $fooDoc->documentElement->getFirstSameAs('foo');
    }

    /**
     * @dataProvider getFirstSameAsSuffixProvider
     */
    public function testGetFirstSameAsSuffix($suffix, $expectedText): void
    {
        $fooDoc = Document::newFromPathname(self::DATA_DIR . 'foo.xml');

        $this->assertSame(
            $expectedText,
            (string)$fooDoc->documentElement->getFirstSameAsSuffix($suffix)
        );
    }

    public function getFirstSameAsSuffixProvider(): array
    {
        return [
            [ 'biz#a', 'Lorem ipsum' ],
            [ 'biz#b', 'dolor sit amet' ],
            [ 'edu#s', 'sadipscing' ]
        ];
    }

    public function testCreateNsPrefix(): void
    {
        $fooDoc = Document::newFromPathname(self::DATA_DIR . 'foo.xml');

        $element = $fooDoc->documentElement;

        /* case 1: use canonical prefix */

        $this->assertSame('dc', $element->createNsPrefix(Document::DC_NS));

        $this->assertSame(
            Document::DC_NS,
            $element->lookupNamespaceURI('dc')
        );

        /* case 2: do not use canonical prefix because it is in use */

        $this->assertSame('ns1', $element->createNsPrefix(Document::RDF_NS));

        $this->assertSame(
            Document::RDF_NS,
            $element->lookupNamespaceURI('ns1')
        );

        /* case 3: use next available ns*i* prefix */

        $nsName = 'http://ns.example.com';

        $this->assertSame('ns2', $element->createNsPrefix($nsName));

        $this->assertSame($nsName, $element->lookupNamespaceURI('ns2'));
    }
}
