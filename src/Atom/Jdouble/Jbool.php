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
       $this->validate($operator);
       
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
                if (!$left->getVar()) {
                    throw new \Exception('Assignment can only be done to variable');                    
                } 
                $left->getVar()->setDouble((int)$right->getBool());
                $left->setDouble((int)$right->getBool());               
           break;
       }
   }
}