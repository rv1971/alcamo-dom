<?php

namespace alcamo\dom\psvi;

use alcamo\dom\decorated\AbstractElementDecorator;

class FooLiteral extends AbstractElementDecorator
{
    public function hello(): string
    {
        return "Hello! $this";
    }
}
