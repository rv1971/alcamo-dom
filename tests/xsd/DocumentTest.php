<?php

namespace alcamo\dom\xsd;

use PHPUnit\Framework\TestCase;
use alcamo\dom\extended\DocumentFactory;

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
