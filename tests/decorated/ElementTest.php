<?php

namespace alcamo\dom\decorated;

use PHPUnit\Framework\TestCase;
use alcamo\exception\MethodNotFound;
use alcamo\xml\XName;

class FooBar extends AbstractElementDecorator
{
    public function hello(): string
    {
        return "Hello, I'm {$this->{'xml:id'}}!";
    }
}

class FooLiteral extends AbstractElementDecorator
{
    public function hello(): string
    {
        return "Hello! $this";
    }
}

class FooShort extends AbstractElementDecorator
{
    public function hello(): string
    {
        return "Hello!";
    }
}

class FooElement extends Element
{
    public const FOO_NS  = 'http://foo.example.org';
    public const RDFS_NS = 'http://www.w3.org/2000/01/rdf-schema#';

    public const DECORATOR_MAP =
        [
            self::FOO_NS => [ 'bar' => FooBar::class ],
            self::RDFS_NS => [ 'comment' => FooLiteral::class ]
        ]
        + parent::DECORATOR_MAP;

    public const DEFAULT_DECORATOR_CLASS = FooShort::class;
}

class FooDocument extends Document
{
    public const NODE_CLASSES =
        [
            'DOMElement' => FooElement::class
        ]
        + parent::NODE_CLASSES;
}

class ElementTest extends TestCase
{
    public function testDecoration()
    {
        $doc = FooDocument::newFromUrl(
            'file://' . dirname(__DIR__) . '/foo.xml'
        );

        $this->assertSame("Hello, I'm x!", $doc['x']->hello());

        $this->assertSame($doc['x'], $doc['x']->getElement());

        $this->assertSame(
            'Hello! Lorem ipsum',
            $doc->documentElement->firstChild->hello()
        );
    }

    public function testException()
    {
        $doc = FooDocument::newFromUrl(
            'file://' . dirname(__DIR__) . '/foo.xml'
        );

        $this->expectException(MethodNotFound::class);
        $this->expectExceptionMessage(
            'Method "bar" not found in object '
            . '<alcamo\dom\decorated\FooBar>"At eosveroC oc"'
        );

        $doc['x']->bar();
    }
}
