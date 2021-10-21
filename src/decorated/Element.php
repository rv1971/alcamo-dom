<?php

/**
 * @file
 *
 * @brief Class Element.
 */

namespace alcamo\dom\decorated;

use alcamo\exception\MethodNotFound;
use alcamo\dom\psvi\Element as BaseElement;

/**
 * @brief Element implementing the decorator pattern
 *
 * @date Last reviewed 2021-07-12
 */
class Element extends BaseElement
{
    private $decorator_ = false; ///< ?AbstractDecorator

    /// The decorator object
    public function getDecorator(): ?AbstractDecorator
    {
        if ($this->decorator_ === false) {
            // Ensure conservation of the derived object.
            $this->register();

            $this->decorator_ = $this->ownerDocument->createDecorator($this);
        }

        return $this->decorator_;
    }

    /// Delegate method calls to the decorator object, if any
    public function __call(string $name, array $params)
    {
        /* Call a method in the decorator only if it exists. Otherwise the
         * decorator would look for it in the Element class, leading to an
         * infinite recursion that end up in a stack overflow. */
        if (method_exists($this->getDecorator(), $name)) {
            return call_user_func_array(
                [ $this->getDecorator(), $name ],
                $params
            );
        } else {
            /** @throw alcamo::exception::MethodNotFound if the method does
             *  not exist in the decorator object. */
            throw (new MethodNotFound())->setMessageContext(
                [
                    'object' => $this->getDecorator(),
                    'method' => $name
                ]
            );
        }
    }
}
