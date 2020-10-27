<?php

namespace Vvoina\Zakerzon\Atom\Jbool;

use Vvoina\Zakerzon\Atom\Joiner;
use Vvoina\Zakerzon\Atom;

/**
 * @author Vasyl Voina <vasyl.voina@gmail.com>
 */
class Jdouble extends Joiner
{
    protected $operators = [
        '=', '||'
    ];

    public function join($operator, Atom $left, Atom $right)
    {
        $this->validate($operator, $right->getType());

        switch ($operator) {
            case '||' :
                $left->setBool(
                    $left->getBool() || ((bool)$right->getDouble())
                );
            break;
            case '=' :
                $left->setBool((bool)$right->getDouble());
            break;
        }
    }
}