<?php

namespace alcamo\dom\psvi;

use alcamo\dom\decorated\AbstractElementDecorator;

class FooBar extends AbstractElementDecorator
{
    public function hello(): string
    {
        return "Hello, I'm {$this->{'xml:id'}}!";
    }
}
