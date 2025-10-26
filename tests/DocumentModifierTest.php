<?php

namespace alcamo\dom;

use PHPUnit\Framework\TestCase;

class DocumentModifierTest extends TestCase
{
    public function testStripXsdDocumentation()
    {
        $documentModifier = new DocumentModifier();

        $doc1 = Document::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'fooWithDocumenation.xml'
        );

        $this->assertSame(
            22,
            $documentModifier->stripXsdDocumentation($doc1)
                ->getElementsByTagName('baz')[0]->getLineNo()
        );

        $doc2 = Document::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'fooWithDocumenation.xml'
        );

        $doc2->formatOutput = true;

        $this->assertSame(
            7,
            $documentModifier->stripXsdDocumentation(
                $doc2,
                DocumentModifier::VALIDATE
                    | DocumentModifier::FORMAT_AND_REPARSE
            )
                ->getElementsByTagName('baz')[0]->getLineNo()
        );

        $this->assertSame(
            file_get_contents('fooWithDocumenation.stripped.xml'),
            $doc2->saveXML()
        );
    }
}
