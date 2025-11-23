<?php

namespace alcamo\dom\psvi;

use alcamo\dom\decorated\AbstractElementDecorator;

class FooShort extends AbstractElementDecorator
{
    public function hello(): string
    {
        return "Hello!";
    }
}
