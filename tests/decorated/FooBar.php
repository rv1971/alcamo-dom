<?php

namespace alcamo\dom\decorated;

class FooBar extends AbstractDecorator
{
    public function hello(): string
    {
        return "Hello, I'm {$this->{'xml:id'}}!";
    }
}
