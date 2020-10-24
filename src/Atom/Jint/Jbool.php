<?php

namespace Vvoina\Zakerzon\Atom\Jint;

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
        $this->validate($operator, $right->getType());
        
        switch ($operator) {
            case '==' :
                $left->setBool(
                    (bool) ($left->getInt()) == $right->getBool()
                );
            break;
            case '||' :
                $left->setBool(
                    (bool) ($left->getInt()) || $right->getBool()
                );
            break;
            case '&&' :
                $left->setBool(
                    (bool) ($left->getInt()) && $right->getBool()
                );
            break;
            case '=' :
                $left->setInt((int)$right->getBool());
            break;
        }
    }
}