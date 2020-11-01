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
        '.', '=', 'like'
    ];

    public function join($operator, Atom $left, Atom $right)
    {
        $this->validate($operator);
        
        switch ($operator) {
            case 'like' :
                $left->setBool(
                    preg_match('#' . $right->getString() . '#', $left->getString())
                );
            break; 
            case '.' :
                $left->setString(
                    $left->getString() . $right->getString()
                );
            break; 
            case '=' :
                if (!$left->getVar()) {
                    throw new \Exception('Assignment can only be done to variable');                    
                } 
                $left->getVar()->setString($right->getString());
                $left->setString($right->getString()); 
            break;            
        }
    }
}