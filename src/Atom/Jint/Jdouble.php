<?php

namespace Vvoina\Zakerzon\Atom\Jint;

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
                    (double) ($left->getInt() + $right->getDouble())
                );
            break;
            case '-' :
                $left->setDouble(
                    (double) ($left->getInt() - $right->getDouble())
                );
            break;
            case '*' :
                $left->setDouble(
                    (double) ($left->getInt() * $right->getDouble())
                );
            break;
            case '/' :
                if ($right->getDouble() == 0) {
                    throw new \Exception('Division by zero');
                }
                $left->setDouble(
                    (double) ($left->getInt() / $right->getDouble())
                );
            break;
            case '%' :
                $left->setDouble(
                    (double) ($left->getInt() % $right->getDouble())
                );
            break;
            case '==' :
                $left->setBool(
                    (bool) ($left->getInt() == $right->getDouble())
                );
            break;
            case '>=' :
                $left->setBool(
                    (bool) ($left->getInt() >= $right->getDouble())
                );
            break;
            case '<=' :
                $left->setBool(
                    (bool) ($left->getInt() <= $right->getDouble())
                );
            break;
            case '||' :
                $left->setBool(
                    (bool) ($left->getInt() || $right->getDouble())
                );
            break;
            case '&&' :
                $left->setBool(
                    (bool) ($left->getInt() && $right->getDouble())
                );
            break;
            case '=' :
                $left->setInt((int)$right->getDouble());
            break;
        }
    }
}