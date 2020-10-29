<?php

namespace Vvoina\Zakerzon\Atom\Jstring;

use Vvoina\Zakerzon\Atom\Joiner;
use Vvoina\Zakerzon\Atom;

/**
 * @author Vasyl Voina <vasyl.voina@gmail.com>
 */
class Jarray extends Joiner
{
    protected $operators = [
        'in'
    ];

    public function join($operator, Atom $left, Atom $right)
    {
        $this->validate($operator, $right->getType());

        switch ($operator) {
            case 'in' :
                $found = false;
                foreach ($right->getArray() as $atomElement) {
                    if ($atomElement->getType() == Atom::TYPE_STRING) {
                        if ($atomElement->getString() == $left->getString()) {
                            $found = true;
                            break;
                        }
                   }
                }
                $left->setBool($found);
            break;           
        }
    }
}