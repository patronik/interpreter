# interpreter

## Intro
This is an interpreter - enhanced calculator. Has basic math features and variables.

## Why do we need this?
Sometimes you just want to know how things work. Then you read about interpreters and sometimes you try to create your own. Created with educational purposes.

## Usage

```
<?php

require_once 'interpreter.php';

$inter = new Interpreter();
try {
    $res = $inter->evaluate(<<<CODE
b = 190;
if (10 > 100) {
    2 + 2;
} else {
    return 7 + 3;
}
CODE
);
    echo $res . "\n";
} catch (Exception $e) {
    echo $e->getMessage();
}
```

Output:
```
10
```

## Conclusions
Interpreters are everywhere. This interpreter can be used as starting point in creating something more serious.
