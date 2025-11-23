<?php

namespace alcamo\dom\decorated;

class FooLiteral extends AbstractElementDecorator
{
    public function hello(): string
    {
        return "Hello! $this";
    }
}
