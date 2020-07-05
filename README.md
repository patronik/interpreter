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
if (2 > 1) {
    if (32 > 11) {
        result = 2;
    } else {
        result = 7;
    }
}
return result;
CODE
);
    echo $res . "\n";
} catch (Exception $e) {
    echo $e->getMessage();
}
```

Output:
```
2
```

## Conclusions
Interpreters are everywhere. This interpreter can be used as starting point in creating something more serious.
