<?php

namespace Vvoina\Zakerzon\Atom\Jint;

use Vvoina\Zakerzon\Atom\Joiner;
use Vvoina\Zakerzon\Atom;

/**
 * @author Vasyl Voina <vasyl.voina@gmail.com>
 */
class Jarray extends Joiner
{
    protected $operators = [];

    public function join($operator, Atom $left, Atom $right)
    {
        $this->validate($operator, $right->getType());
    }
}