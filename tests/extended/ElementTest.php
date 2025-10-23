<?php

namespace alcamo\dom\extended;

use PHPUnit\Framework\TestCase;
use alcamo\rdfa\Lang;

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
                Lang::newFromPrimary('oc')
            ],
            'parent-lang' => [
                $fooDoc->documentElement->firstChild->nextSibling,
                Lang::newFromPrimary('oc')
            ],
            'closest-parent-lang' => [
                $fooDoc['qux'],
                Lang::newFromPrimary('fi')
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
                $doc->documentElement, 'xml:lang', true, Lang::newFromPrimary('oc')
            ],
            'xname' => [
                $doc->documentElement,
                Document::XML_NS . ' lang',
                true,
                Lang::newFromPrimary('oc')
            ],
            'unset-without-namespace' => [
                $doc->documentElement, 'barbarbar', false, null
            ],
            'unset-namespace-prefix' => [
                $doc->documentElement, 'dc:title', false, null
            ],
            'unset-xname' => [
                $doc->documentElement,
                Document::RDFS_NS . ' comment',
                false,
                null
            ]
        ];
    }
}
