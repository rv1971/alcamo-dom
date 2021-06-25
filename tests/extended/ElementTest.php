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
            ->setAttributeNS(Document::NS['xml'], 'xml:lang', 'cu');

        $this->assertEquals('cu', $doc->documentElement
            ->getAttributeNS(Document::NS['xml'], 'lang'));

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
        $this->assertSame($expectedValue, $elem->$attrName);
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
                $doc->documentElement, 'xml:lang', true, 'oc'
            ],
            'xname' => [
                $doc->documentElement, Document::NS['xml'] . ' lang', true, 'oc'
            ],
            'unset-without-namespace' => [
                $doc->documentElement, 'barbarbar', false, null
            ],
            'unset-namespace-prefix' => [
                $doc->documentElement, 'dc:title', false, null
            ],
            'unset-xname' => [
                $doc->documentElement,
                Document::NS['rdfs'] . ' comment',
                false,
                null
            ]
        ];
    }
}
