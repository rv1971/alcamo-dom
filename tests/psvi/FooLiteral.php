<?php

namespace alcamo\dom\psvi;

use alcamo\dom\decorated\AbstractDecorator;

class FooLiteral extends AbstractDecorator
{
    public function hello(): string
    {
        return "Hello! $this";
    }
}
