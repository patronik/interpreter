<?php

namespace Vvoina\Zakerzon\Atom\Jcast;

use Vvoina\Zakerzon\Atom\Joiner;
use Vvoina\Zakerzon\Atom;

/**
 * @author Vasyl Voina <vasyl.voina@gmail.com>
 */
class Jbool extends Joiner
{
    protected $operators = [
        'cast',
    ];

    public function join($operator, Atom $left, Atom $right)
    {
        $this->validate($operator);

        switch ($operator) {
            case 'cast' :
                switch ($left->getCast()) {
                    case 'int' :
                        $right->setInt((int) $right->getBool());
                    break;
                    case 'string' :
                        $right->setString((string) $right->getBool());
                    break;
                    case 'double' :
                        $right->setDouble((double) $right->getBool());
                    break;
                    case 'bool' :
                    break;
                    case 'array' :
                        $right->setArray([$right->getBool()]);
                    break;
                    case 'nool' :
                        $right->setNool(null);
                    break;
                }
            break;
        }
    }
}