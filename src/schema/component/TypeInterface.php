<?php

namespace alcamo\dom\schema\component;

interface TypeInterface extends ComponentInterface
{
    public function getBaseType(): ?self;
}
