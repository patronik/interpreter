<?php

require __DIR__.'/../vendor/autoload.php';

use Vvoina\Zakerzon\Interpreter;

ini_set('display_errors', 1);
error_reporting(E_ALL);

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
    echo $e->getMessage() . "<br/>";
}

$code = '
if (false || 1) {
    a = 2;
}
';
echo "keyword atom<br/>";
echo "code: <b>" . nl2br($code) . "</b>";
try {
    $inter = new Interpreter();
    $inter->setReturnLast(true);
    $res = $inter->evaluate($code);
    echo "res: " . $res . "<br/>";
    echo ($res == 2) ? 'OK' : 'FAIL!';
    echo "<br/><br/>";
} catch (Exception $e) {
    echo $e->getMessage() . "<br/>";
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
    echo $e->getMessage() . "<br/>";
}

$code = '
(2 + 2 * 5) + 1;
';
echo "subexpression<br/>";
echo "code: <b>" . nl2br($code) . "</b>";
try {
    $inter = new Interpreter();
    $inter->setReturnLast(true);
    $res = $inter->evaluate($code);
    echo "res: " . $res . "<br/>";
    echo ($res == 13) ? 'OK' : 'FAIL!';
    echo "<br/><br/>";
} catch (Exception $e) {
    echo $e->getMessage() . "<br/>";
}

$code = '
2.5 / 2;
';
echo "floating point numbers<br/>";
echo "code: <b>" . nl2br($code) . "</b>";
try {
    $inter = new Interpreter();
    $inter->setReturnLast(true);
    $res = (float) $inter->evaluate($code);
    echo "res: " . $res . "<br/>";
    echo ($res == 1.25) ? 'OK' : 'FAIL!';
    echo "<br/><br/>";
} catch (Exception $e) {
    echo $e->getMessage() . "<br/>";
}


$code = '
(int)(5 / 2);
';
echo "type casting<br/>";
echo "code: <b>" . nl2br($code) . "</b>";
try {
    $inter = new Interpreter();
    $inter->setReturnLast(true);
    $res = $inter->evaluate($code);
    echo "res: " . $res . "<br/>";
    echo ($res == 2) ? 'OK' : 'FAIL!';
    echo "<br/><br/>";
} catch (Exception $e) {
    echo $e->getMessage() . "<br/>";
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
    echo $e->getMessage() . "<br/>";
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
    echo $e->getMessage() . "<br/>";
}

$code = '
2 in [1,2];
';
echo "in array<br/>";
echo "code: <b>" . nl2br($code) . "</b>";
try {
    $inter = new Interpreter();
    $inter->setReturnLast(true);
    $res = $inter->evaluate($code);
    echo "res: " . $res . "<br/>";
    echo ($res == 1) ? 'OK' : 'FAIL!';
    echo "<br/><br/>";
} catch (Exception $e) {
    echo $e->getMessage() . "<br/>";
}

$code = '
if (2 > 1) {
    return 5;
} else {
    return 4;
}
';
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
    echo $e->getMessage() . "<br/>";
}

$code = '
i = 5;
for (a = 0; a < 5; a++)
{
    if (a > 2) {
        break;
    }
}
return a + i;
';
echo "for loop<br/>";
echo "code: <b>" . nl2br($code) . "</b>";
try {
    $inter = new Interpreter();
    $inter->setReturnLast(true);
    $res = $inter->evaluate($code);
    echo "res: " . $res . "<br/>";
    echo ($res == 8) ? 'OK' : 'FAIL!';
    echo "<br/><br/>";
} catch (Exception $e) {
    echo $e->getMessage() . "<br/>";
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
    echo $e->getMessage() . "<br/>";
}

$code = '
return "abc" like ".*c.*";
';
echo "regular expression<br/>";
echo "code: <b>" . nl2br($code) . "</b>";
try {
    $inter = new Interpreter();
    $inter->setReturnLast(true);
    $res = $inter->evaluate($code);
    echo "res: " . $res . "<br/>";
    echo ($res == 1) ? 'OK' : 'FAIL!';
    echo "<br/><br/>";
} catch (Exception $e) {
    echo $e->getMessage() . "<br/>";
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

data = 0;
for (i = 0; i < 3; i++) {
    data += result["max"] + result["min"];
}
return data;
';
echo "multiple features<br/>";
echo "code: <b>" . nl2br($code) . "</b>";
try {
    $inter = new Interpreter();
    $inter->setReturnLast(true);
    $res = $inter->evaluate($code);
    echo "res: " . $res . "<br/>";
    echo ($res == 39) ? 'OK' : 'FAIL!';
    echo "<br/><br/>";
} catch (Exception $e) {
    echo $e->getMessage() . "<br/>";
}
