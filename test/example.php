<?php

use Vvoina\Zakerzon\Interpreter;

ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
$inter = new Interpreter();
$inter->setReturnLast(true);
$res = $inter->evaluate(<<<CODE
"a" in [2,55, 'a'] && "zebra" like ".*z.*";
CODE
);
    echo $res;
} catch (Exception $e) {
    echo $e->getMessage();
}

