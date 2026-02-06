<?php

namespace alcamo\dom\extended;

use alcamo\rdfa\Lang;
use alcamo\uri\{FileUriFactory, Uri};
use alcamo\xml\XName;
use PHPUnit\Framework\TestCase;

class MyAttr extends Attr
{
    public const ATTR_CONVERTERS =
        [
            'https://baz.example.edu#' => [
                'special' => __CLASS__ . '::convertSpecial'
            ]
        ]
    + parent::ATTR_CONVERTERS;

    public static function convertSpecial(string $value): ?string
    {
        return $value !== '' ? "+$value+" : null;
    }
}

class MyDocument extends Document
{
    public const NS_PRFIX_TO_NS_URI =
        [
            'baz' => 'https://baz.example.edu#'
        ]
        + parent::NS_PRFIX_TO_NS_URI;

    public const NODE_CLASSES =
        [
            'DOMAttr'    => MyAttr::class,
        ]
        + parent::NODE_CLASSES;
}

class ElementTest extends TestCase
{
    public const DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR;

    private static $factory_;
    private static $doc_;

    public static function setUpBeforeClass(): void
    {
        self::$factory_ =
            new DocumentFactory((new FileUriFactory())->create(self::DATA_DIR));

        self::$doc_ =
            self::$factory_->createFromUri('foo.xml', MyDocument::class, false);
    }

    /* This tests trait HavingLangTrait. */
    public function testGetLang(): void
    {
        $fooDoc = self::$factory_->createFromUri('foo.xml', null, false);

        $this->assertSame(0, $fooDoc->getNodeRegistrySize());

        $this->assertEquals(
            Lang::newFromPrimary('is'),
            $fooDoc->documentElement->getLang()
        );

        $this->assertSame(1, $fooDoc->getNodeRegistrySize());

        $this->assertEquals(
            Lang::newFromPrimary('fo'),
            $fooDoc->documentElement->firstChild->firstChild->getLang()
        );

        $this->assertSame(2, $fooDoc->getNodeRegistrySize());

        $this->assertEquals(
            Lang::newFromPrimary('fo'),
            $fooDoc->documentElement->firstChild->getLang()
        );

        $this->assertSame(3, $fooDoc->getNodeRegistrySize());

        $this->assertEquals(
            Lang::newFromPrimary('no'),
            $fooDoc['xh1']->getLang()
        );

        $this->assertEquals(
            Lang::newFromPrimary('no'),
            $fooDoc['xh2']->getLang()
        );

        $this->assertEquals(
            Lang::newFromPrimary('fi'),
            $fooDoc['xh3']->getLang()
        );
    }

    /* This tests trait HavingLangTrait. */
    public function testGetPosition(): void
    {
        $factory = new DocumentFactory(
            (new FileUriFactory())->create(self::DATA_DIR)
        );

        $fooDoc = $factory->createFromUri('foo.xml', null, false);

        foreach ($fooDoc->documentElement as $i => $child) {
            $this->assertSame($i, $child->getPosition());

            $this->assertSame($i + 1, $fooDoc->getNodeRegistrySize());
        }
    }

    /**
     * @dataProvider magicAttrAccessProvider
     */
    public function testMagicAttrAccess($xPath, $attrName, $expectedValue): void
    {
        /* This also tests class Attr. */

        $element = self::$doc_->query($xPath)[0];

        $this->assertSame($element->textContent, (string)$element);

        $this->assertSame($element->textContent, $element->getValue());

        if (isset($expectedValue)) {
            $this->assertTrue(isset($element->$attrName));
        } else {
            $this->assertFalse(isset($element->$attrName));
        }

        $this->assertEquals($expectedValue, $element->$attrName);

        /* Now the cache is used. */
        if (isset($expectedValue)) {
            $this->assertTrue(isset($element->$attrName));
        } else {
            $this->assertFalse(isset($element->$attrName));
        }
    }

    public function magicAttrAccessProvider(): array
    {
        return [
            [ '*', 'xml:lang', Lang::newFromPrimary('is') ],
            [ '*', 'owl:sameAs', new Uri('foo.json') ],
            [ '*', 'xsi:nil', false ],
            [
                '*',
                Document::XSI_NS . ' type',
                new XName('https://bar.example.info/', 'bar')
            ],
            [ '*', 'https://baz.example.edu# special', null ],
            [ '*/xh:p', 'about', new Uri('https://bar.example.info/foo') ],
            [ '*', 'baz', 'BAZ' ],
            [ '*/*[2]', 'baz:special', '+Lorem ipsum+' ]
        ];
    }

    /* This tests that the element is cached once there is an entry in the
     * attribute cache. */
    public function testElementCache(): void
    {
        $fooDoc = self::$factory_->createFromUri('foo.xml', null, false);

        $hash1 = spl_object_hash($fooDoc->documentElement->firstChild);

        $hash2 = spl_object_hash($fooDoc->documentElement->firstChild);

        $this->assertSame(0, $fooDoc->getNodeRegistrySize());

        $this->assertFalse($hash2 == $hash1);

        $fooDoc->documentElement->firstChild->about;

        $this->assertSame(2, $fooDoc->getNodeRegistrySize());

        $hash3 = spl_object_hash($fooDoc->documentElement->firstChild);

        $this->assertSame($hash1, $hash3);
    }

    /* This tests caching in Attr */
    public function testAttrCaching(): void
    {
        $attr = self::$doc_->documentElement
            ->getAttributeNodeNS(Document::OWL_NS, 'sameAs');

        $uri = $attr->getValue();

        $this->assertInstanceof(Uri::class, $uri);

        $this->assertSame($uri, $attr->getValue());
    }

    /* This tests caching in Element */
    public function testMagicAttrAccessCaching(): void
    {
        $uri = self::$doc_->documentElement->{'owl:sameAs'};

        $this->assertInstanceof(Uri::class, $uri);

        $this->assertSame($uri, self::$doc_->documentElement->{'owl:sameAs'});

        $attrName = Document::OWL_NS . ' sameAs';

        $this->assertSame($uri, self::$doc_->documentElement->$attrName);
    }
}
