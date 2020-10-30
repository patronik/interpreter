<?php

namespace Vvoina\Zakerzon\Atom\Jstring;

use Vvoina\Zakerzon\Atom\Joiner;
use Vvoina\Zakerzon\Atom;

/**
 * @author Vasyl Voina <vasyl.voina@gmail.com>
 */
class Jdouble extends Joiner
{
    protected $operators = [
        '.', '='
    ];

    public function join($operator, Atom $left, Atom $right)
    {
        $this->validate($operator);
        
        switch ($operator) {
            case '.' :
                $left->setString(
                    $left->getString() . ((string) $right->getDouble())
                );
            break;
            case '=' :
                if (!$left->getVarRef()) {
                    throw new \Exception('Assignment can only be done to variable');                    
                } 
                $left->getVarRef()->setString((string)$right->getDouble());
                $left->setString((string)$right->getDouble());
            break;            
        }
    }
}