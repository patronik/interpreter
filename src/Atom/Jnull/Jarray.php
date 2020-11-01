<?php

namespace Vvoina\Zakerzon\Atom\Jnull;

use Vvoina\Zakerzon\Atom\Joiner;
use Vvoina\Zakerzon\Atom;

/**
 * @author Vasyl Voina <vasyl.voina@gmail.com>
 */
class Jarray extends Joiner
{
    protected $operators = [
        '=',
    ];

    public function join($operator, Atom $left, Atom $right)
    {
        $this->validate($operator);

        switch ($operator) {
            case '=' :
                if (!$left->getVar()) {
                    throw new \Exception('Assignment can only be done to variable');                    
                } 
                $left->getVar()->setArray($right->getArray());
                $left->setArray($right->getArray());
            break;
        }
    }
}