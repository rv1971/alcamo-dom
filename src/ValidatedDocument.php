<?php

namespace alcamo\dom;

/**
 * @brief Use ValidationTrait to add validation to afterLoad() hook
 *
 * @date Last reviewed 2021-07-01
 */
class ValidatedDocument extends Document
{
    use ValidationTrait;
}
