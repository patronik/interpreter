# interpreter

## Intro
This is an interpreter - enhanced calculator. Has basic math features and variables.

## Why do we need this?
Sometimes you just want to know how things work. Then you read about interpreters and sometimes you try to create your own. Created with educational purposes.

## Supported features
1. Math operations
2. Variables
3. Integer and associative arrays
4. Conditional operator
5. For loop
6. Subprograms (functions)

## Usage

```
<?php

require_once 'interpreter.php';

$inter = new Interpreter();
try {
    $res = $inter->evaluate(<<<CODE
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
    data = data + result["max"] + result["min"];
}
return data;
CODE
);
    echo $res . "\n";
} catch (Exception $e) {
    echo $e->getMessage();
}
```

Output:
```
39
```

## Conclusions
Interpreters are everywhere. This interpreter can be used as starting point in creating something more serious.
