<?php

namespace Vvoina\Zakerzon\Atom\Jint;

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
        $this->validate($operator);

        switch ($operator) {
            case 'in' :
                $found = false;
                foreach ($right->getArray() as $atomElement) {
                    if ($atomElement->getType() == Atom::TYPE_INT) {
                        if ($atomElement->getInt() == $left->getInt()) {
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