<?php

namespace alcamo\dom\extended;

use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class DocumentTest extends TestCase
{
    public const DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR;

    public function testClone(): void
    {
        $factory =
            new DocumentFactory((new FileUriFactory())->create(self::DATA_DIR));

        $fooDoc = $factory->createFromUri('foo.xml', null, false);

        $this->assertSame(0, $fooDoc->getNodeRegistrySize());

        $fooDoc->documentElement->getLang();

        $this->assertSame(1, $fooDoc->getNodeRegistrySize());

        $fooDoc2 = clone $fooDoc;

        $this->assertSame(0, $fooDoc2->getNodeRegistrySize());
    }
}
