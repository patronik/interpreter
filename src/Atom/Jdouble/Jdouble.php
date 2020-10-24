<?php

namespace Vvoina\Zakerzon\Atom\Jdouble;

use Vvoina\Zakerzon\Atom\Joiner;
use Vvoina\Zakerzon\Atom;

/**
 * @author Vasyl Voina <vasyl.voina@gmail.com>
 */
class Jdouble extends Joiner
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
                    (double) ($left->getDouble() + $right->getDouble())
                );
            break;
            case '-' :
                $left->setDouble(
                    (double) ($left->getDouble() - $right->getDouble())
                );
            break;
            case '*' :
                $left->setDouble(
                    (double) ($left->getDouble() * $right->getDouble())
                );
            break;
            case '/' :
                if ($right->getDouble() == 0) {
                    throw new \Exception('Division by zero');
                }
                $left->setDouble(
                    (double) ($left->getDouble() / $right->getDouble())
                );
            break;
            case '%' :
                $left->setDouble(
                    (double) ($left->getDouble() % $right->getDouble())
                );
            break;
            case '==' :
                $left->setBool(
                    (bool) ($left->getDouble() == $right->getDouble())
                );
            break;
            case '>=' :
                $left->setBool(
                    (bool) ($left->getDouble() >= $right->getDouble())
                );
            break;
            case '<=' :
                $left->setBool(
                    (bool) ($left->getDouble() <= $right->getDouble())
                );
            break;
            case '||' :
                $left->setBool(
                    (bool) ($left->getDouble() || $right->getDouble())
                );
            break;
            case '&&' :
                $left->setBool(
                    (bool) ($left->getDouble() && $right->getDouble())
                );
            break;
            case '=' :
                $left->setDouble($right->getDouble());
            break;
        }
    }
}