<?php

namespace Vvoina\Zakerzon\Atom\Jstring;

use Vvoina\Zakerzon\Atom\Joiner;
use Vvoina\Zakerzon\Atom;

/**
 * @author Vasyl Voina <vasyl.voina@gmail.com>
 */
class Jint extends Joiner
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
                    $left->getString() . ((string) $right->getInt())
                );
            break;
            case '=' :
                if (!$left->getVar()) {
                    throw new \Exception('Assignment can only be done to variable');                    
                } 
                $left->getVar()->setString((string)$right->getInt());
                $left->setString((string)$right->getInt());
            break;             
        }
    }
}