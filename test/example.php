<?php

require __DIR__.'/../vendor/autoload.php';

use Vvoina\Zakerzon\Interpreter;

ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
$inter = new Interpreter();
$inter->setReturnLast(true);
$res = $inter->evaluate(<<<CODE
students = [
    "Stepan" => ["age" => 16, "score" => 5],
    "Bogdan" => ["age" => 17, "score" => 4]
    ];
    return students["Bogdan"]["age"] * 2;
CODE
);
    echo $res;
} catch (Exception $e) {
    echo $e->getMessage();
}



