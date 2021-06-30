<?php

namespace alcamo\dom\xsd;

use PHPUnit\Framework\TestCase;
use alcamo\xml\XName;

class ElementTest extends TestCase
{
    /**
     * @dataProvider getUniqueNameProvider
     */
    public function testGetUniqueName($elem, $expectedName)
    {
        $this->assertEquals($expectedName, $elem->getComponentXName());
    }

    public function getUniqueNameProvider()
    {
        $doc = Document::newFromUrl(
            dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR
            . 'xsd' . DIRECTORY_SEPARATOR . 'XMLSchema.xsd'
        )->conserve();

        return [
            'type' => [
                $doc->query('//*[@name = "openAttrs"]')[0],
                new XName(Document::XSD_NS, 'openAttrs')
            ],
            'annotation' => [
                $doc->query('//*[@name = "openAttrs"]/*')[0],
                null
            ],
            'attribute' => [
                $doc->query('//*[@name = "id"]')[0],
                new XName(null, 'id')
            ]
        ];
    }
}
