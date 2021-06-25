<?php

namespace alcamo\dom\extended;

use PHPUnit\Framework\TestCase;

class DocumentTest extends TestCase
{
    public function testGetDocumentFactory()
    {
        $doc = Document::newFromUrl(
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'foo.xml'
        );

        $this->assertInstanceOf(
            DocumentFactory::class,
            $doc->getDocumentFactory()
        );
    }
}
