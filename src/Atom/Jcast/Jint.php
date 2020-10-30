<?php

namespace Vvoina\Zakerzon\Atom\Jcast;

use Vvoina\Zakerzon\Atom\Joiner;
use Vvoina\Zakerzon\Atom;

/**
 * @author Vasyl Voina <vasyl.voina@gmail.com>
 */
class Jint extends Joiner
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
                    break;
                    case 'string' :
                        $right->setString((string) $right->getInt());
                    break;
                    case 'double' :
                        $right->setInt((int) $right->getInt());
                    break;
                    case 'bool' :
                        $right->setBool((bool) $right->getInt());
                    break;
                    case 'array' :
                        $right->setArray([$right->getInt()]);
                    break;
                    case 'nool' :
                        $right->setNool(null);
                    break;
                }
            break;
        }
    }
}