<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\decorated\Element as XsdElement;
use alcamo\dom\schema\Schema;
use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class UnionTypeTest extends TestCase
{
    public const FOO_NS = 'http://foo.example.org';

    public function testProps(): void
    {
        $fooUri = (new FileUriFactory())
            ->create(__DIR__ . DIRECTORY_SEPARATOR . 'foo.xsd');

        /* Contains XMLSchema.xsd as built-in. */
        $schema = Schema::newFromUris([ $fooUri ]);

        $type =
            $schema->getGlobalType(self::FOO_NS . ' UnionType');

        $this->assertSame(
            [
                self::FOO_NS . ' nonNegativeFloat',
                Schema::XSD_NS . ' positiveInteger'
            ],
            array_keys($type->getMemberTypes())
        );

        foreach ($type->getMemberTypes() as $xName => $memberType) {
            $this->assertSame($xName, (string)$memberType->getXName());
        }

        $this->assertNull($type->getFacet('minInclusive'));

        $whiteSpaceFacet = $type->getFacet('whiteSpace');

        $this->assertInstanceof(XsdElement::class, $whiteSpaceFacet);
        $this->assertSame('collapse', $whiteSpaceFacet->value);
        $this->assertSame(true, $whiteSpaceFacet->fixed);

        $this->assertTrue($type->isNumeric());
        $this->assertFalse($type->isIntegral());
    }
}
