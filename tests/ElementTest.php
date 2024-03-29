<?php

namespace alcamo\dom;

use alcamo\exception\AbsoluteUriNeeded;
use PHPUnit\Framework\TestCase;

class ElementTest extends TestCase
{
    public function testIteration()
    {
        $doc = Document::newFromUrl(__DIR__ . DIRECTORY_SEPARATOR . 'foo.xml');

        $bar = $doc['x'];

        $expectedIds = [
            'a', 'b', 'c', 'd',
            'datetime',
            'duration',
            'float',
            'intset',
            'lang',
            'media-type',
            'longint',
            'ref',
            'bool-1',
            'bool-0',
            'base64',
            'hex',
            'pairsToMap',
            'curie',
            'safecurie',
            'uriorsafecurie1',
            'uriorsafecurie2',
            'document',
            'xpointer1',
            'xpointer2',
            ''
        ];

        foreach ($bar as $pos => $baz) {
            $this->assertSame(
                $expectedIds[$pos],
                (string)$baz->getAttributeNodeNS(Document::XML_NS, 'id')
            );
        }
    }

    public function testRfc5147Trait()
    {
        $doc = Document::newFromUrl(__DIR__ . DIRECTORY_SEPARATOR . 'foo.xml');

        $this->assertSame(
            $doc->documentURI . '#line=30,31',
            $doc['a']->getRfc5147Uri()
        );

        $this->assertSame(
            $doc->documentURI . '#line=58,59',
            $doc['xpointer2']->getAttributeNode('content')->getRfc5147Uri()
        );
    }

    public function testGetSameAs()
    {
        $doc = Document::newFromUrl(__DIR__ . DIRECTORY_SEPARATOR . 'foo.xml');

        $this->assertSame(
            'c',
            $doc->documentElement
                ->getFirstSameAs("http://baz.example.org/'bar'/#c")
                ->getAttribute('xml:id')
        );

        $this->assertSame(
            'c',
            $doc->getElementById('c')
                ->getFirstSameAs("http://baz.example.org/'bar'/#c")
                ->getAttribute('xml:id')
        );

        $this->assertNull(
            $doc->documentElement
                ->getFirstSameAs("http://foo.example.org/'bar'/#c")
        );

        $relUri = "baz.xml#c";

        $this->expectException(AbsoluteUriNeeded::class);
        $this->expectExceptionMessage(
            "Relative URI <alcamo\uri\Uri>\"$relUri\" "
            . "given where absolute URI is needed"
        );

        $doc->documentElement->getFirstSameAs($relUri);
    }
}
