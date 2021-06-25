<?php

namespace alcamo\dom;

/// Always validate after load.
trait ValidationTrait
{
    protected function afterLoad()
    {
        $this->validate();
    }
}
