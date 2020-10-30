<?php

namespace Vvoina\Zakerzon\Atom;

use Vvoina\Zakerzon\Atom;

/**
 * @author Vasyl Voina <vasyl.voina@gmail.com>
 */
abstract class Joiner
{
    protected $operators = [];

    protected function validate($operator)
    {
        if (!in_array($operator, $this->operators)) {
            throw new \Exception('Operator '. $operator . ' is not supported by joiner ' . get_class($this));
        }
    }
    
    abstract public function join($operator, Atom $left, Atom $right);
}