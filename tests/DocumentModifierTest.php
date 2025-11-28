<?php

namespace alcamo\dom;

use alcamo\exception\DataValidationFailed;
use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class DocumentModifierTest extends TestCase
{
    public const DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR;

    public function testStripXsdDocumentation(): void
    {
        $factory =
            new DocumentFactory((new FileUriFactory())->create(self::DATA_DIR));

        $modifier = new DocumentModifier();

        /* foo.xml remains unchanged. */

        $foo = $factory->createFromUri('foo.xsd');

        $fooOrig = clone $foo;

        $this->assertSame($foo, $modifier->stripXsdDocumentation($foo));

        $this->assertSame($fooOrig->saveXML(), $foo->saveXml());

        /* In bar.xml, the first <xsd:annotation> will not contain
         * <xsd:documentation> any more, while the second <xsd:annotation>
         * will be removed completely. */

        $bar = $factory->createFromUri('bar.xml');

        $this->assertSame(2.0, $bar->evaluate('count(*/*/xsd:annotation)'));

        $barOrig = clone $bar;

        $this->assertSame(
            $bar,
            $modifier->stripXsdDocumentation($bar, DocumentModifier::VALIDATE)
        );

        $this->assertFalse($barOrig->saveXML() == $bar->saveXml());

        $this->assertSame(1.0, $bar->evaluate('count(*/*/xsd:annotation)'));

        $this->assertSame(
            0.0,
            $bar->evaluate('count(*/*/xsd:annotation/xsd:documentation)')
        );

        $this->assertSame(
            2.0,
            $bar->evaluate('count(*/*/xsd:annotation/xsd:appinfo)')
        );

        /* The node with ID `appinfo` keeps its line number unless the result
         * document is reparsed. */

        $this->assertSame(19, $bar['appinfo2']->getLineNo());

        $bar2 = clone $barOrig;

        $modifier->stripXsdDocumentation(
            $bar2,
            DocumentModifier::FORMAT_AND_REPARSE
        );

        $this->assertSame(6, $bar2['appinfo2']->getLineNo());

        $invalidBar = $factory->createFromUri('invalid-bar-1.xml');

        $this->expectException(DataValidationFailed::class);

        $modifier->stripXsdDocumentation(
            $invalidBar,
            DocumentModifier::VALIDATE
        );
    }
}
