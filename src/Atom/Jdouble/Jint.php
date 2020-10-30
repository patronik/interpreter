<?php

namespace Vvoina\Zakerzon\Atom\Jdouble;

use Vvoina\Zakerzon\Atom\Joiner;
use Vvoina\Zakerzon\Atom;

/**
 * @author Vasyl Voina <vasyl.voina@gmail.com>
 */
class Jint extends Joiner
{
    protected $operators = [
        '+', '-', '*', '/', '%', '==', '>=', '<=', '||', '&&', '='
    ];

    public function join($operator, Atom $left, Atom $right)
    {
        $this->validate($operator);
        
        switch ($operator) {
            case '+' :
                $left->setDouble(
                    (double) ($left->getDouble() + $right->getInt())
                );
            break;
            case '-' :
                $left->setDouble(
                    (double) ($left->getDouble() - $right->getInt())
                );
            break;
            case '*' :
                $left->setDouble(
                    (double) ($left->getDouble() * $right->getInt())
                );
            break;
            case '/' :
                if ($right->getInt() == 0) {
                    throw new \Exception('Division by zero');
                }
                $left->setDouble(
                    (double) ($left->getDouble() / $right->getInt())
                );
            break;
            case '%' :
                $left->setDouble(
                    (double) ($left->getDouble() % $right->getInt())
                );
            break;
            case '==' :
                $left->setBool(
                    (bool) ($left->getDouble() == $right->getInt())
                );
            break;
            case '>=' :
                $left->setBool(
                    (bool) ($left->getDouble() >= $right->getInt())
                );
            break;
            case '<=' :
                $left->setBool(
                    (bool) ($left->getDouble() <= $right->getInt())
                );
            break;
            case '||' :
                $left->setBool(
                    (bool) ($left->getDouble() || $right->getInt())
                );
            break;
            case '&&' :
                $left->setBool(
                    (bool) ($left->getDouble() && $right->getInt())
                );
            break;
            case '=' :
                if (!$left->getVarRef()) {
                    throw new \Exception('Assignment can only be done to variable');                    
                } 
                $left->getVarRef()->setDouble((double)$right->getInt());
                $left->setDouble((double)$right->getInt()); 
            break;
        }
    }
}