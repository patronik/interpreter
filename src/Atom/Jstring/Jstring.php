<?php

namespace Vvoina\Zakerzon\Atom\Jstring;

use Vvoina\Zakerzon\Atom\Joiner;
use Vvoina\Zakerzon\Atom;

/**
 * @author Vasyl Voina <vasyl.voina@gmail.com>
 */
class Jstring extends Joiner
{
    protected $operators = [
        '.', '='
    ];

    public function join($operator, Atom $left, Atom $right)
    {
        $this->validate($operator, $right->getType());
        
        switch ($operator) {
            case '.' :
                $left->setString(
                    $left->getString() . $right->getString()
                );
            break; 
            case '=' :
                $left->setString($right->getString());
            break;            
        }
    }
}