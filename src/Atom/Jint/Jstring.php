<?php

namespace Vvoina\Zakerzon\Atom\Jint;

use Vvoina\Zakerzon\Atom\Joiner;
use Vvoina\Zakerzon\Atom;

/**
 * @author Vasyl Voina <vasyl.voina@gmail.com>
 */
class Jstring extends Joiner
{
    protected $operators = [
        '.'
    ];

    public function join($operator, Atom $left, Atom $right)
    {
        $this->validate($operator);
        
        switch ($operator) {
            case '.' :
                $left->setString(
                    ((string) $left->getInt()) . $right->getString()
                );
            break;            
        }
    }
}