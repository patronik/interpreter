<?php

namespace Vvoina\Zakerzon\Atom\Jcast;

use Vvoina\Zakerzon\Atom\Joiner;
use Vvoina\Zakerzon\Atom;

/**
 * @author Vasyl Voina <vasyl.voina@gmail.com>
 */
class Jdouble extends Joiner
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
                        $right->setInt((int) $right->getDouble());
                    break;
                    case 'string' :
                        $right->setString((string) $right->getDouble());
                    break;
                    case 'double' :
                    break;
                    case 'bool' :
                        $right->setBool((bool) $right->getDouble());
                    break;
                    case 'array' :
                        $right->setArray([$right->getDouble()]);
                    break;
                    case 'nool' :
                        $right->setNool(null);
                    break;
                }
            break;
        }
    }
}