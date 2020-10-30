<?php

namespace Vvoina\Zakerzon\Atom\Jarray;

use Vvoina\Zakerzon\Atom\Joiner;
use Vvoina\Zakerzon\Atom;

/**
 * @author Vasyl Voina <vasyl.voina@gmail.com>
 */
class Jnull extends Joiner
{
    protected $operators = [];

    public function join($operator, Atom $left, Atom $right)
    {
        $this->validate($operator);
    }
}