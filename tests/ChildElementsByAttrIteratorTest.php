<?php

namespace alcamo\dom;

use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class ChildElementsByAttrIteratorTest extends TestCase
{
    public const DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR;

    public function testIteration(): void
    {
        $fooDoc = Document::newFromUri(
            (new FileUriFactory())->create(self::DATA_DIR . 'foo.xml')
        );

        $data = [];

        foreach (
            new ChildElementsByAttrIterator($fooDoc['bar'], 'id') as $i => $text
        ) {
            $data[$i] = (string)$text;
        }

        $this->assertSame(
            [
                'A' => 'Lorem ipsum',
                'B' => 'dolor sit amet',
                'C' => 'consetetur sadipscing elitr'
            ],
            $data
        );
    }
}
