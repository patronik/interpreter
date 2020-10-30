<?php

namespace Vvoina\Zakerzon\Atom\Jint;

use Vvoina\Zakerzon\Atom\Joiner;
use Vvoina\Zakerzon\Atom;

/**
 * @author Vasyl Voina <vasyl.voina@gmail.com>
 */
class Jint extends Joiner
{
    protected $operators = [
        '+', '-', '*', '/', '%', '==', '>', '<', '>=', '<=', '||', '&&', '='
    ];

    public function join($operator, Atom $left, Atom $right)
    {
        $this->validate($operator);
        
        switch ($operator) {
            case '+' :
                $left->setInt(
                    (int) ($left->getInt() + $right->getInt())
                );
            break;
            case '-' :
                $left->setInt(
                    (int) ($left->getInt() - $right->getInt())
                );
            break;
            case '*' :
                $left->setInt(
                    (int) ($left->getInt() * $right->getInt())
                );
            break;
            case '/' :
                if ($right->getInt() == 0) {
                    throw new \Exception('Division by zero');
                }
                $left->setInt(
                    (int) ($left->getInt() / $right->getInt())
                );
            break;
            case '%' :
                $left->setDouble(
                    (double) ($left->getInt() % $right->getInt())
                );
            break;
            case '==' :
                $left->setBool(
                    (bool) ($left->getInt() == $right->getInt())
                );
            break;
            case '>=' :
                $left->setBool(
                    (bool) ($left->getInt() >= $right->getInt())
                );
            break;
            case '>' :
                $left->setBool(
                    (bool) ($left->getInt() > $right->getInt())
                );
            break;
            case '<' :
                $left->setBool(
                    (bool) ($left->getInt() < $right->getInt())
                );
            break;
            case '<=' :
                $left->setBool(
                    (bool) ($left->getInt() <= $right->getInt())
                );
            break;
            case '||' :
                $left->setBool(
                    (bool) ($left->getInt() || $right->getInt())
                );
            break;
            case '&&' :
                $left->setBool(
                    (bool) ($left->getInt() && $right->getInt())
                );
            break;
            case '=' :
                if (!$left->isVar()) {
                    throw new \Exception('Assignment can only be done to variable');                    
                } 
                $left->getVarRef()->setInt($right->getInt());
                $left->setInt($right->getInt());
            break;
        }
    }
}