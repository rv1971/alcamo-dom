<?php

namespace alcamo\dom;

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
            'lang',
            'media-type',
            'longint',
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
            'xpointer2'
        ];
        $i = 0;

        foreach ($bar as $baz) {
            $this->assertSame(
                $expectedIds[$i++],
                (string)$baz->getAttributeNodeNS(Document::NS['xml'], 'id')
            );
        }
    }

    public function testRfc5147Trait()
    {
        $doc = Document::newFromUrl(__DIR__ . DIRECTORY_SEPARATOR . 'foo.xml');

        $this->assertSame(
            $doc->documentURI . '#line=27',
            $doc['a']->getRfc5147Uri()
        );

        $this->assertSame(
            $doc->documentURI . '#line=50',
            $doc['xpointer2']->getAttributeNode('content')->getRfc5147Uri()
        );
    }
}
