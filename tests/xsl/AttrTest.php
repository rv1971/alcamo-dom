<?php

namespace alcamo\dom\xsl;

use PHPUnit\Framework\TestCase;
use alcamo\dom\extended\Element;
use alcamo\iana\MediaType;

class AttrTest extends TestCase
{
    public function testGetValue()
    {
        $doc = Document::newFromUrl(
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'foo.xsl'
        );

        $outputElem = $doc->query('//xsl:output')[0];

        $this->assertInstanceOf(Element::class, $outputElem);

        $this->assertEquals(
            new MediaType('application', 'json'),
            $outputElem->{'media-type'}
        );

        $this->assertSame(false, $outputElem->indent);
    }
}
