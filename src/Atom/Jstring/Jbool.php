<?php

namespace Vvoina\Zakerzon\Atom\Jstring;

use Vvoina\Zakerzon\Atom\Joiner;
use Vvoina\Zakerzon\Atom;

/**
 * @author Vasyl Voina <vasyl.voina@gmail.com>
 */
class Jbool extends Joiner
{
    protected $operators = [
        '==', '||', '&&'
   ];

   public function join($operator, Atom $left, Atom $right)
   {
       $this->validate($operator);
       
       switch ($operator) {
           case '==' :
               $left->setBool(
                   (bool) ($left->getString()) == $right->getBool()
               );
           break;
           case '||' :
               $left->setBool(
                   (bool) ($left->getString()) || $right->getBool()
               );
           break;
           case '&&' :
               $left->setBool(
                   (bool) ($left->getString()) && $right->getBool()
               );
           break;
       }
   }
}