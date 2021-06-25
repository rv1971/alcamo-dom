<?php

namespace alcamo\dom\schema\component;

/// Defintion of an interface for enumeration types
interface EnumerationTypeInterface extends SimpleTypeInterface
{
    public function getEnumerators(): array;
}
