<?php

namespace Vvoina\Zakerzon\Atom\Jcast;

use Vvoina\Zakerzon\Atom\Joiner;
use Vvoina\Zakerzon\Atom;

/**
 * @author Vasyl Voina <vasyl.voina@gmail.com>
 */
class Jnull extends Joiner
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
                        throw new \Exception('Cannot convert nool to int');                   
                    break;
                    case 'string' :
                        throw new \Exception('Cannot convert nool to string');
                    break;
                    case 'double' :
                        throw new \Exception('Cannot convert nool to double');
                    break;
                    case 'bool' :
                        $right->setBool(false);
                    break;
                    case 'array' :
                        throw new \Exception('Cannot convert nool to array');
                    break;
                    case 'nool' :
                    break;
                }
            break;
        }
    }
}