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
        $this->validate($operator, $right->getType());
        
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
                $left->setDouble((double)$right->getInt());
            break;
        }
    }
}