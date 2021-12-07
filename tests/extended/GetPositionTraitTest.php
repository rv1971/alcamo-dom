<?php

namespace alcamo\dom\extended;

use PHPUnit\Framework\TestCase;

class MyElement extends Element
{
    use GetPositionTrait;
}

class MyDocument extends Document
{
    public const NODE_CLASSES =
        [
            'DOMElement' => MyElement::class
        ]
        + parent::NODE_CLASSES;
}

class GetPositionTraitTest extends TestCase
{
    /**
     * @dataProvider positionProvider
     */
    public function testPosition($elem, $expectedPosition)
    {
        $this->assertEquals($expectedPosition, $elem->getPosition());
    }

    public function positionProvider()
    {
        $fooDoc = MyDocument::newFromUrl(
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'foo.xml'
        )->conserve();

        return [
            [ $fooDoc->documentElement, 1 ],
            [ $fooDoc['a'], 1 ],
            [ $fooDoc['b'], 2 ],
            [ $fooDoc['d'], 4 ],
            [ $fooDoc['corge'], 3 ]
        ];
    }
}
