<?php

namespace alcamo\dom\extended;

use alcamo\exception\UnknownNamespacePrefix;
use alcamo\rdf_literal\Lang;
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
    public const NS_PRFIX_TO_NS_NAME =
        [
            'baz' => 'https://baz.example.edu#'
        ]
        + parent::NS_PRFIX_TO_NS_NAME;

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
    }

    public function setUp(): void
    {
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
     * @dataProvider getAttrNodeProvider
     */
    public function testGetAttrNode(
        $attrName,
        $expectedAttrName2,
        $expectedNsName,
        $expectedLocalName,
        $expectedAttrExists
    ): void {
        $attrNode = self::$doc_->documentElement
            ->getAttrNode($attrName, $attrName2, $nsName, $localName);

        $this->assertSame($expectedAttrName2, $attrName2);
        $this->assertSame($expectedNsName, $nsName);
        $this->assertSame($expectedLocalName, $localName);
        $this->assertTrue(
            $expectedAttrExists
                ? $attrNode instanceof Attr
                : $attrNode === null
        );

        if ($expectedAttrExists) {
            $this->assertSame($expectedNsName, $attrNode->namespaceURI);
            $this->assertSame($expectedLocalName, $localName);
        }
    }

    public function getAttrNodeProvider(): array
    {
        return [
            [ 'bar', null, null, 'bar', false ],
            [ 'baz', null, null, 'baz', true ],
            [
                'dc:identifier',
                Document::DC_NS . ' identifier',
                Document::DC_NS,
                'identifier',
                false
            ],
            [
                'xml:lang',
                Document::XML_NS . ' lang',
                Document::XML_NS,
                'lang',
                'true'
            ],
            [
                Document::DC_NS . ' creator',
                'dc:creator',
                Document::DC_NS,
                'creator',
                false
            ],
            [
                Document::XSI_NS . ' nil',
                'xsi:nil',
                Document::XSI_NS,
                'nil',
                true
            ],
            [
                'http://foo.example.org corge',
                null,
                'http://foo.example.org',
                'corge',
                false
            ],
            [
                'https://baz.example.edu# special',
                null,
                'https://baz.example.edu#',
                'special',
                true
            ]
        ];
    }

    public function testGetAttrNodeException(): void
    {
        $this->expectException(UnknownNamespacePrefix::class);

        $this->expectExceptionMessage(
            'Unknown namespace prefix "foo" in "foo:special"'
        );

        self::$doc_->documentElement->getAttrNode('foo:special');
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

    public function testSet(): void
    {
        /* case 0: assign null */

        $this->assertTrue(self::$doc_->documentElement->hasAttribute('baz'));

        $this->assertSame('BAZ', self::$doc_->documentElement->baz);

        $nodeRegistrySize = self::$doc_->getNodeRegistrySize();

        self::$doc_->documentElement->baz = null;

        $nodeRegistrySize2 = self::$doc_->getNodeRegistrySize();

        $this->assertSame($nodeRegistrySize - 1, $nodeRegistrySize2);

        $this->assertFalse(self::$doc_->documentElement->hasAttribute('baz'));

        $this->assertNull(self::$doc_->documentElement->baz);

        /* case 1a: add attribute without namespace */

        $nodeRegistrySize = self::$doc_->getNodeRegistrySize();

        self::$doc_->documentElement->baz = 1234;

        $nodeRegistrySize2 = self::$doc_->getNodeRegistrySize();

        $this->assertSame($nodeRegistrySize + 1, $nodeRegistrySize2);

        $this->assertTrue(self::$doc_->documentElement->hasAttribute('baz'));

        $this->assertSame('1234', self::$doc_->documentElement->baz);

        /* case 1b: modify attribute without namespace */

        $nodeRegistrySize = self::$doc_->getNodeRegistrySize();

        self::$doc_->documentElement->baz = 'BAZ-BAZ';

        $nodeRegistrySize2 = self::$doc_->getNodeRegistrySize();

        $this->assertSame($nodeRegistrySize, $nodeRegistrySize2);

        $this->assertTrue(self::$doc_->documentElement->hasAttribute('baz'));

        $this->assertSame('BAZ-BAZ', self::$doc_->documentElement->baz);

        /* case 2a: add attribute with namespace prefix */

        $nodeRegistrySize = self::$doc_->getNodeRegistrySize();

        self::$doc_->documentElement->{'dc:identifier'} = 'foo';

        $nodeRegistrySize2 = self::$doc_->getNodeRegistrySize();

        $this->assertSame($nodeRegistrySize + 1, $nodeRegistrySize2);

        $this->assertTrue(
            self::$doc_->documentElement
                ->hasAttributeNS(Document::DC_NS, 'identifier')
        );

        $this->assertSame(
            'foo',
            self::$doc_->documentElement->{'dc:identifier'}
        );

        /* case 2b: modify attribute with namespace prefix */

        $nodeRegistrySize = self::$doc_->getNodeRegistrySize();

        self::$doc_->documentElement->{'dc:identifier'} = 'foo-bar';

        $nodeRegistrySize2 = self::$doc_->getNodeRegistrySize();

        $this->assertSame($nodeRegistrySize, $nodeRegistrySize2);

        $this->assertTrue(
            self::$doc_->documentElement
                ->hasAttributeNS(Document::DC_NS, 'identifier')
        );

        $this->assertSame(
            'foo-bar',
            self::$doc_->documentElement->{'dc:identifier'}
        );

        /* case 3a: add attribute with namespace name */

        $nodeRegistrySize = self::$doc_->getNodeRegistrySize();

        $attrName = Document::RDFS_NS . ' label';

        self::$doc_->documentElement->$attrName = 'Foo';

        $nodeRegistrySize2 = self::$doc_->getNodeRegistrySize();

        $this->assertSame($nodeRegistrySize + 1, $nodeRegistrySize2);

        $this->assertTrue(
            self::$doc_->documentElement
                ->hasAttributeNS(Document::RDFS_NS, 'label')
        );

        $this->assertSame('Foo', self::$doc_->documentElement->$attrName);

        /* case 3b: modify attribute with namespace name */

        $nodeRegistrySize = self::$doc_->getNodeRegistrySize();

        self::$doc_->documentElement->$attrName = 'Foo element';

        $nodeRegistrySize2 = self::$doc_->getNodeRegistrySize();

        $this->assertSame($nodeRegistrySize, $nodeRegistrySize2);

        $this->assertTrue(
            self::$doc_->documentElement
                ->hasAttributeNS(Document::RDFS_NS, 'label')
        );

        $this->assertSame(
            'Foo element',
            self::$doc_->documentElement->$attrName
        );
    }

    public function testUnset(): void
    {
        /* case 1: attribute w/o namespace */

        $this->assertTrue(self::$doc_->documentElement->hasAttribute('baz'));

        $this->assertSame('BAZ', self::$doc_->documentElement->baz);

        $nodeRegistrySize = self::$doc_->getNodeRegistrySize();

        unset(self::$doc_->documentElement->baz);

        $nodeRegistrySize2 = self::$doc_->getNodeRegistrySize();

        $this->assertSame($nodeRegistrySize - 1, $nodeRegistrySize2);

        $this->assertFalse(self::$doc_->documentElement->hasAttribute('baz'));

        $this->assertNull(self::$doc_->documentElement->baz);

        /* case 2: attribute with namespace prefix */

        $attrName = Document::OWL_NS . ' sameAs';

        $this->assertTrue(
            self::$doc_->documentElement
                ->hasAttributeNS(Document::OWL_NS, 'sameAs')
        );

        $this->assertSame(
            'foo.json',
            (string)self::$doc_->documentElement->{'owl:sameAs'}
        );

        $this->assertSame(
            'foo.json',
            (string)self::$doc_->documentElement->$attrName
        );

        $nodeRegistrySize = self::$doc_->getNodeRegistrySize();

        unset(self::$doc_->documentElement->{'owl:sameAs'});

        $nodeRegistrySize2 = self::$doc_->getNodeRegistrySize();

        $this->assertSame($nodeRegistrySize - 1, $nodeRegistrySize2);

        $this->assertFalse(
            self::$doc_->documentElement
                ->hasAttributeNS(Document::OWL_NS, 'sameAs')
        );

        $this->assertNull(self::$doc_->documentElement->{'owl:sameAs'});

        $this->assertNull(self::$doc_->documentElement->$attrName);

        /* case 3: attribute with namespace name */

        $attrName = Document::XML_NS . ' lang';

        $this->assertTrue(
            self::$doc_->documentElement
                ->hasAttributeNS(Document::XML_NS, 'lang')
        );

        $this->assertSame(
            'is',
            (string)self::$doc_->documentElement->{'xml:lang'}
        );

        $this->assertSame(
            'is',
            (string)self::$doc_->documentElement->$attrName
        );

        $nodeRegistrySize = self::$doc_->getNodeRegistrySize();

        unset(self::$doc_->documentElement->$attrName);

        $nodeRegistrySize2 = self::$doc_->getNodeRegistrySize();

        $this->assertSame($nodeRegistrySize - 1, $nodeRegistrySize2);

        $this->assertFalse(
            self::$doc_->documentElement
                ->hasAttributeNS(Document::XML_NS, 'lang')
        );

        $this->assertNull(self::$doc_->documentElement->{'xml:lang'});

        $this->assertNull(self::$doc_->documentElement->$attrName);
    }
}
