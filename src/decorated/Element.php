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
 * @brief Element implementing the decorator pattern.
 *
 * The DOM framework has no means to generate different subclasses of
 * DOMElement for different XML element types. This class allows to delegate
 * element-type-specific functionality to a decorator object.
 */
class Element extends BaseElement
{
    private $decorator_ = false; ///< ?AbstractDecorator

    public function getDecorator()
    {
        if ($this->decorator_ === false) {
            // Ensure conservation of the derived object.
            $this->register();

            $this->decorator_ = $this->ownerDocument->createDecorator($this);
        }

        return $this->decorator_;
    }

    public function __call($name, $params)
    {
        /* Call method in the decorator only if it exists. Otherwise the
         * decorator would look for it in the Element class, leading to an
         * infinite recursion that end up in a stack overflow. */
        if (method_exists($this->getDecorator(), $name)) {
            return call_user_func_array(
                [ $this->getDecorator(), $name ],
                $params
            );
        } else {
            throw new MethodNotFound($this->getDecorator(), $name);
        }
    }
}
