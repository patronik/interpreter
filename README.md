# interpreter

## Intro
This is an interpreter - enhanced calculator. Has basic math features and variables.

## Why do we need this?
Sometimes you just want to know how things work. Then you read about interpreters and sometimes you try to create your own. Created with educational purposes.

## Usage

```
$inter = new Interpreter();
try {
    $res = $inter->evaluate('x = 5 + 6 * 7; x + 9');
    echo (int) $res . "\n";
} catch (Exception $e) {
    echo $e->getMessage();
}
```

Output:
```
56
```

## Conclusions
Interpreters are everywhere. This interpreter can be used as starting point in creating something more serious.
