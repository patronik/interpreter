<?php

namespace Vvoina\Zakerzon\Atom\Jdouble;

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
                   (bool) ($left->getDouble()) == $right->getBool()
               );
           break;
           case '||' :
               $left->setBool(
                   (bool) ($left->getDouble()) || $right->getBool()
               );
           break;
           case '&&' :
               $left->setBool(
                   (bool) ($left->getDouble()) && $right->getBool()
               );
           break;
           case '=' :
            $left->setDouble((int)$right->getBool());
           break;
       }
   }
}