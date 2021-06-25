<?php

namespace alcamo\dom\decorated;

class FooLiteral extends AbstractDecorator
{
    public function hello(): string
    {
        return "Hello! $this";
    }
}
