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
        $this->validate($operator);

        switch ($operator) {
            case '||' :
                $left->setBool(
                    $left->getBool() || ((bool)$right->getDouble())
                );
            break;
            case '=' :  
                if (!$left->getVar()) {
                    throw new \Exception('Assignment can only be done to variable');                    
                } 
                $left->getVar()->setBool((bool)$right->getDouble());
                $left->setBool((bool)$right->getDouble());
            break;
        }
    }
}