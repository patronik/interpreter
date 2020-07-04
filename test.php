<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'interpreter.php';

$code = '
2 + 2;
';
echo "addition<br/>";
echo "code: <b>" . nl2br($code) . "</b>";
try {
    $inter = new Interpreter();
    $inter->setReturnLast(true);
    $res = $inter->evaluate($code);
    echo "res: " . $res . "<br/>";
    echo ($res == 4) ? 'OK' : 'FAIL!';
    echo "<br/><br/>";
} catch (Exception $e) {
    echo $e->getMessage();
}

$code = '
2 - 1;
';
echo "subtraction<br/>";
echo "code: <b>" . nl2br($code) . "</b>";
try {
    $inter = new Interpreter();
    $inter->setReturnLast(true);
    $res = $inter->evaluate($code);
    echo "res: " . $res . "<br/>";
    echo ($res == 1) ? 'OK' : 'FAIL!';
    echo "<br/><br/>";
} catch (Exception $e) {
    echo $e->getMessage();
}

$code = '
a = 15;
b = 14;
return a + b * 2;
';
echo "variables<br/>";
echo "code: <b>" . nl2br($code) . "</b>";
try {
    $inter = new Interpreter();
    $inter->setReturnLast(true);
    $res = $inter->evaluate($code);
    echo "res: " . $res . "<br/>";
    echo ($res == 43) ? 'OK' : 'FAIL!';
    echo "<br/><br/>";
} catch (Exception $e) {
    echo $e->getMessage();
}

$code = '
students = [
"Stepan" => ["age" => 16, "score" => 5],
"Bogdan" => ["age" => 17, "score" => 4]
];
return students["Bogdan"]["age"] > students["Stepan"]["age"];
';
echo "arrays<br/>";
echo "code: <b>" . nl2br($code) . "</b>";
try {
    $inter = new Interpreter();
    $inter->setReturnLast(true);
    $res = $inter->evaluate($code);
    echo "res: " . $res . "<br/>";
    echo ($res == 1) ? 'OK' : 'FAIL!';
    echo "<br/><br/>";
} catch (Exception $e) {
    echo $e->getMessage();
}



$code = '
if (2 > 1) {
    return 5;
} else {
    return 4;
}';
echo "conditional operator<br/>";
echo "code: <b>" . nl2br($code) . "</b>";
try {
    $inter = new Interpreter();
    $inter->setReturnLast(true);
    $res = $inter->evaluate($code);
    echo "res: " . $res . "<br/>";
    echo ($res == 5) ? 'OK' : 'FAIL!';
    echo "<br/><br/>";
} catch (Exception $e) {
    echo $e->getMessage();
}

$code = '
sub add(a,b) {
return a + b;
}
return add(5,5);
';
echo "subprogram<br/>";
echo "code: <b>" . nl2br($code) . "</b>";
try {
    $inter = new Interpreter();
    $inter->setReturnLast(true);
    $res = $inter->evaluate($code);
    echo "res: " . $res . "<br/>";
    echo ($res == 10) ? 'OK' : 'FAIL!';
    echo "<br/><br/>";
} catch (Exception $e) {
    echo $e->getMessage();
}

$code = '
a = 2 + 2;
if (a > 5) {
 b = 6;
} else {
 b = 9;
}
sub max(x,y) {
    if (x > y) {
        return x;
    }
    return y;
}
sub min(x,y) {
    if (x < y) {
        return x;
    }
    return y;
}
result = ["max" => max(a,b), "min" => min(a,b)];
return result["max"] + result["min"];
';
echo "multiple features<br/>";
echo "code: <b>" . nl2br($code) . "</b>";
try {
    $inter = new Interpreter();
    $inter->setReturnLast(true);
    $res = $inter->evaluate($code);
    echo "res: " . $res . "<br/>";
    echo ($res == 13) ? 'OK' : 'FAIL!';
    echo "<br/><br/>";
} catch (Exception $e) {
    echo $e->getMessage();
}


