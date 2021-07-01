<?php

namespace alcamo\dom;

/**
 * @brief Add validation to afterLoad() hook
 *
 * @date Last reviewed 2021-07-01
 */
trait ValidationTrait
{
    protected function afterLoad(): void
    {
        parent::afterLoad();
        $this->validate();
    }
}
