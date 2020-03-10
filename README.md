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
age = 18;
height = 190 * 2;
return age + height * (2 + 3) - 70;
CODE
);
    echo $res . "\n";
} catch (Exception $e) {
    echo $e->getMessage();
}
```

Output:
```
1848
```

## Conclusions
Interpreters are everywhere. This interpreter can be used as starting point in creating something more serious.
