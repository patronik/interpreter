<?php

namespace Vvoina\Zakerzon\Atom\Jcast;

use Vvoina\Zakerzon\Atom\Joiner;
use Vvoina\Zakerzon\Atom;

/**
 * @author Vasyl Voina <vasyl.voina@gmail.com>
 */
class Jstring extends Joiner
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
                        $right->setInt((int) $right->getString());                      
                    break;
                    case 'string' :
                    break;
                    case 'double' :
                        $right->setDouble((double) $right->getString());
                    break;
                    case 'bool' :
                        $right->setBool((bool) $right->getString());
                    break;
                    case 'array' :
                        $right->setArray([$right->getString()]);
                    break;
                    case 'nool' :
                        $right->setNool(null);
                    break;
                }
            break;
        }
    }
}