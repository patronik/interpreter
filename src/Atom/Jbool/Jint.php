<?php

namespace Vvoina\Zakerzon\Atom\Jbool;

use Vvoina\Zakerzon\Atom\Joiner;
use Vvoina\Zakerzon\Atom;

/**
 * @author Vasyl Voina <vasyl.voina@gmail.com>
 */
class Jint extends Joiner
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
                    $left->getBool() || ((bool)$right->getInt())
                );
            break;
            case '=' :
                if (!$left->isVar()) {
                    throw new \Exception('Assignment can only be done to variable');                    
                } 
                $left->getVarRef()->setBool((bool)$right->getInt());
                $left->setBool((bool)$right->getInt());                
            break;
        }
    }
}