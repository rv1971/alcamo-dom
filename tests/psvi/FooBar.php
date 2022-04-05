<?php

namespace alcamo\dom\psvi;

use alcamo\dom\decorated\AbstractDecorator;

class FooBar extends AbstractDecorator
{
    public function hello(): string
    {
        return "Hello, I'm {$this->{'xml:id'}}!";
    }
}
