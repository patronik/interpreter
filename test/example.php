<?php

require __DIR__.'/../vendor/autoload.php';

use Vvoina\Zakerzon\Interpreter;
use Vvoina\Zakerzon\Atom;

ini_set('display_errors', 1);
error_reporting(E_ALL);

$atom1 = new Atom();
$atom1->setInt(12);
$atom2 = new Atom();
$atom2->setDouble(3.12);

$atom1->join('/', $atom2);

echo $atom1->getDouble();


