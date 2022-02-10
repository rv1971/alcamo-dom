<?php

namespace alcamo\dom\extended;

use PHPUnit\Framework\TestCase;
use alcamo\ietf\Lang;

class ElementTest extends TestCase
{
    /**
     * @dataProvider langProvider
     */
    public function testLang($elem, $expectedLang)
    {
        $this->assertEquals($expectedLang, $elem->getLang());
    }

    public function langProvider()
    {
        $fooDoc = Document::newFromUrl(
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'foo.xml'
        )->conserve();

        $barDoc = Document::newFromUrl(
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bar.xml'
        )->conserve();

        return [
            'explicit-lang' => [
                $fooDoc->documentElement,
                new Lang('oc')
            ],
            'parent-lang' => [
                $fooDoc->documentElement->firstChild->nextSibling,
                new Lang('oc')
            ],
            'closest-parent-lang' => [
                $fooDoc['qux'],
                new Lang('fi')
            ],
            'no-lang' => [
                $barDoc->documentElement,
                null
            ]
        ];
    }

    public function testLangCache()
    {
        $doc = Document::newFromUrl(
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'foo.xml'
        )->conserve();

        $this->assertEquals('oc', (string)$doc->documentElement->getLang());

        $doc->documentElement
            ->setAttributeNS(Document::XML_NS, 'xml:lang', 'cu');

        $this->assertEquals('cu', $doc->documentElement
            ->getAttributeNS(Document::XML_NS, 'lang'));

        // language is cached and therefore does not see the change
        $this->assertEquals('oc', (string)$doc->documentElement->getLang());
    }

    /**
     * @dataProvider magicAttrAccessProvider
     */
    public function testMagicAttrAccess(
        $elem,
        $attrName,
        $expectedIsSet,
        $expectedValue
    ) {
        $this->assertSame($expectedIsSet, isset($elem->$attrName));

        if (is_object($expectedValue)) {
            $this->assertInstanceOf(
                get_class($expectedValue),
                $elem->$attrName
            );

            $this->assertEquals($expectedValue, $elem->$attrName);
        } else {
            $this->assertSame($expectedValue, $elem->$attrName);
        }
    }

    public function magicAttrAccessProvider()
    {
        $doc = Document::newFromUrl(
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'foo.xml'
        )->conserve();

        return [
            'without-namespace' => [
                $doc->documentElement, 'qux', true, '42-43'
            ],
            'namespace-prefix' => [
                $doc->documentElement, 'xml:lang', true, new Lang('oc')
            ],
            'xname' => [
                $doc->documentElement,
                Document::XML_NS . ' lang',
                true,
                new Lang('oc')
            ],
            'unset-without-namespace' => [
                $doc->documentElement, 'barbarbar', false, null
            ],
            'unset-namespace-prefix' => [
                $doc->documentElement, 'dc:title', false, null
            ],
            'unset-xname' => [
                $doc->documentElement,
                Document::NSS['rdfs'] . ' comment',
                false,
                null
            ]
        ];
    }
}
