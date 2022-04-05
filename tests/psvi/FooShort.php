<?php

namespace alcamo\dom\psvi;

use alcamo\dom\decorated\AbstractDecorator;

class FooShort extends AbstractDecorator
{
    public function hello(): string
    {
        return "Hello!";
    }
}
