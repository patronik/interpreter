<?php

namespace Vvoina\Zakerzon\Atom\Jbool;

use Vvoina\Zakerzon\Atom\Joiner;
use Vvoina\Zakerzon\Atom;

/**
 * @author Vasyl Voina <vasyl.voina@gmail.com>
 */
class Jbool extends Joiner
{
    protected $operators = [
        '==', '||', '&&', '='
    ];

    public function join($operator, Atom $left, Atom $right)
    {
        $this->validate($operator);

        switch ($operator) {
            case '==' :
                $left->setBool(
                    $left->getBool() == $right->getBool()
                );
            break;
            case '||' :
                $left->setBool(
                    $left->getBool() || $right->getBool()
                );
            break;
            case '&&' :
                $left->setBool(
                    $left->getBool() && $right->getBool()
                );
            break;
            case '=' :                
                if (!$left->getVar()) {
                    throw new \Exception('Assignment can only be done to variable');                    
                } 
                $left->getVar()->setBool($right->getBool());
                $left->setBool($right->getBool());
            break;
        }
    }
}