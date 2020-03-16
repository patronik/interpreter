<?php

/**
 * Class Interpreter
 * @author Vasyl Voina <vasyl.voina@gmail.com>
 */

class Interpreter
{
    const STATEMENT_TYPE_RETURN = 'return';
    const STATEMENT_TYPE_IF     = 'if';
    const STATEMENT_TYPE_ELSE   = 'else';

    protected $return = false;

    protected $numOfOpenedBlocks = 0;
    protected $numOfClosedBlocks = 0;

    /**
     * Result of last executed statement
     *
     * @var
     */
    protected $lastResult;

    /**
     * The result of last if statement expression
     *
     * @var
     */
    protected $lastIfResult = [];

    /**
     * Path to current code block
     *
     * @var array
     */
    protected $scopePath = [];

    /**
     * Variables
     *
     * @var array
     */
    protected $var = [];

    /**
     * @var string
     */
    protected $src;

    /**
     * @var int
     */
    protected $pos;

    /**
     * Dynamic characters buffer
     */
    protected $dynamicQueue = [];

    /**
     * @param string $key
     * @param mixed $val
     */
    public function setVar($key, $val): void
    {
        $this->var[$key] = $val;
    }

    /**
     * Check if character is considered as space character
     *
     * @param $char
     * @return bool
     */
    protected function isSpace($char)
    {
        // 33 last not visible ASCII character
        return ord($char) < 33;
    }

    /**
     * We don't want to analyze space character, skip them
     */
    protected function skipSpaces() : void
    {
        while($this->pos < strlen($this->src)
            && $this->isSpace($this->src[$this->pos]))
        {
            $this->pos++;
        }
    }

    /**
     * Read and return next character
     *
     * @param $toLower
     * @param $allChars
     * @throws Exception
     * @return string|null
     */
    protected function readChar($toLower = false, $allChars = false)
    {
        if (count($this->dynamicQueue)) {
            return array_shift($this->dynamicQueue);
        }
        if (!$allChars) {
            $this->skipSpaces();
        }
        if ($this->pos >= strlen($this->src)) {
            return null;
        }
        $char = $this->src[$this->pos++];
        if ($toLower) {
            $char = strtolower($char);
        }
        return $char;
    }

    /**
     * Return last char(s)
     *
     * @param int $numOfSteps
     */
    protected function unreadChar($numOfSteps = 1)
    {
        do {
            $this->pos--;
            while ($this->isSpace($this->src[$this->pos])) {
                $this->pos -= 1;
            }
            $numOfSteps--;
        } while ($numOfSteps > 0);
    }

    /**
     * Before math atom is returned, some pre operations can be performed
     *
     * @param $operator
     * @param $val
     * @return int
     */
    protected function applyPreOperator($operator, $val)
    {
        switch ($operator) {
            case '-' :
                return -$val;
                break;
            case '+' :
                return +$val;
                break;
            case '++' :
                return ++$val;
                break;
            case '--' :
                return --$val;
                break;
        }
    }

    /**
     * @param $char
     * @param $varName
     * @return bool
     * @throws Exception
     */
    protected function parseVariableName($char, &$varName)
    {
        $asciiCode = ord($char);
        if ($asciiCode >= 65 && $asciiCode <= 90 // A-Z
            || $asciiCode >= 97 && $asciiCode <= 122) // a-z
        {
            $varName = $char;
            // var name
            while (!is_null($char = $this->readChar(false, true))) {
                $asciiCode = ord($char);
                if ($asciiCode >= 65 && $asciiCode <= 90 // A-Z
                    || $asciiCode >= 97 && $asciiCode <= 122 // a-z
                    || $asciiCode >= 48 && $asciiCode <= 57 // 0-9
                    || $asciiCode == 95) // _
                {
                    $varName .= $char;
                    continue;
                }
                if (!$this->isSpace($char)) {
                    $this->unreadChar();
                }
                break;
            }
            return true;
        }
        return false;
    }

    /**
     * @param $char
     * @param $atom
     * @return bool
     * @throws Exception
     */
    protected function evaluateSubexpression($char, &$atom)
    {
        if ($char == '(') {
            $atom = $this->evaluateBoolStatement();
            if ($this->readChar() != ')') {
                throw new Exception('Syntax error. Wrong number of parentheses.');
            }
            return true;
        }
        return false;
    }

    /**
     * @param $char
     * @param $atom
     * @return bool
     * @throws Exception
     */
    protected function evaluateVariableAtom($char, &$atom)
    {
        // variable
        $varName = null;
        if ($this->parseVariableName($char, $varName)) {
            // The place where we can implement accessing object methods and properties
            if (!isset($this->var[$varName])) {
                throw new Exception('Variable ' . $varName . ' does not exist.');
            }
            $atom = $this->var[$varName];
            return true;
        }
        return false;
    }

    /**
     * @param $char
     * @param $atom
     * @return bool
     * @throws Exception
     */
    protected function evaluateSingleQuotedStringAtom($char, &$atom)
    {
        // string in single quotes
        if ($char == "'") {
            $atom = '';
            while (!is_null($char = $this->readChar(false, true))) {
                if ($char != "'") {
                    $atom .= $char;
                    continue;
                } else if (strlen($atom) > 0 && $atom[strlen($atom) - 1] == "\\") {
                    $atom[strlen($atom) - 1] = '\'';
                    continue;
                }
                break;
            }
            return true;
        }
        return false;
    }

    /**
     * @param $char
     * @param $atom
     * @return bool
     * @throws Exception
     */
    protected function evaluateDoubleQuotedStringAtom($char, &$atom)
    {
        // string in double quotes
        if ($char == "\"") {
            $atom = "";
            while (!is_null($char = $this->readChar(false, true))) {
                if ($char != "\"") {
                    $atom .= $char;
                    continue;
                } else if (strlen($atom) > 0 && $atom[strlen($atom) - 1] == "\\") {
                    $atom[strlen($atom) - 1] = "\"";
                    continue;
                }
                break;
            }
            return true;
        }
        return false;
    }

    /**
     * @param $char
     * @param $atom
     * @return bool
     * @throws Exception
     */
    protected function evaluateNumberAtom($char, &$atom)
    {
        // number
        $asciiCode = ord($char);
        if ($asciiCode >= 48 && $asciiCode <= 57) { // 0-9
            $atom = $char;
            while (!is_null($char = $this->readChar(false, true))) {
                $asciiCode = ord($char);
                if ($asciiCode >= 48 && $asciiCode <= 57 // 0-9
                    || $asciiCode == 46) // .
                {
                    $atom .= $char;
                    continue;
                }
                if (!$this->isSpace($char)) {
                    $this->unreadChar();
                }
                break;
            }
            return true;
        }
        return false;
    }

    /**
     * The atomic (indivisible) part of math expression
     *
     * @return bool|int|mixed|string|null
     * @throws Exception
     */
    protected function evaluateMathAtom()
    {
        $atom = null;
        $boolInversion = false;
        $preOperator = false;

        $atomChar = $this->readChar();

        if (is_null($atomChar)) {
            return $atom;
        }

        // check for boolean inversion
        if ($atomChar == '!') {
            $boolInversion = true;
            $atomChar = $this->readChar();
        }

        // unary plus and pre increment
        if ($atomChar == '+') {
            $preOperator = $atomChar;
            $atomChar = $this->readChar();
            // check for pre increment
            if ($atomChar == '+') {
                $preOperator .= $atomChar;
                $atomChar = $this->readChar();
            }
        }

        // unary minus and pre decrement
        if ($atomChar == '-') {
            $preOperator = $atomChar;
            $atomChar = $this->readChar();
            // check for pre decrement
            if ($atomChar == '-') {
                $preOperator .= $atomChar;
                $atomChar = $this->readChar();
            }
        }

        // handle subexpression
        if ($this->evaluateSubexpression($atomChar, $atom)) {
            return $atom;
        }

        if (!$this->evaluateVariableAtom($atomChar, $atom)) {
            if (!$this->evaluateNumberAtom($atomChar, $atom)) {
                if (!$this->evaluateSingleQuotedStringAtom($atomChar, $atom)) {
                    $this->evaluateDoubleQuotedStringAtom($atomChar, $atom);
                }
            }
        }

        if ($preOperator) {
            $atom = $this->applyPreOperator($preOperator, $atom);
        }

        if ($boolInversion) {
            $atom = !$atom;
        }

        return $atom;
    }

    /**
     * Single math block
     *
     * @return int
     */
    protected function evaluateMathBlock()
    {
        $result = $this->evaluateMathAtom();
        while ($atomOp = $this->readChar(true)) {
            switch ($atomOp) {
                case '*':
                    $result *= $this->evaluateMathAtom();
                    break;
                case '/':
                    $result /= $this->evaluateMathAtom();
                    break;
                case '.':
                    $result .= $this->evaluateMathAtom();
                    break;
                case '%':
                    $result %= $this->evaluateMathAtom();
                    break;
                case '+':
                    $nextChar = $this->readChar();
                    if ($nextChar == '+') {
                        $result++;
                    } else {
                        // Lower lever operator
                        $this->unreadChar(2);
                        return $result;
                    }
                    break;
                case '-':
                    $nextChar = $this->readChar();
                    if ($nextChar == '-') {
                        $result--;
                    } else {
                        // Lower lever operator
                        $this->unreadChar(2);
                        return $result;
                    }
                    break;
                // Lower lever operators
                case '=': // equality ===
                case '!': // boolean not
                case '>': // less than
                case '<': // greater than
                case '&': // boolean "and" &&
                case '|': // boolean "or" ||
                case 'l': // check against regex
                case 'i': // find in set
                    // end of subexpression
                case ')':
                    // end of statement
                case ';':
                    // start of statement block
                    $this->unreadChar();
                    return $result;
                    break;
                default:
                    throw new Exception('Unexpected token ' . $atomOp . '.');
                    break;
            }
        }
        return $result;
    }

    /**
     * Single boolean expression that consists from one or more math blocks
     *
     * @return bool|int
     * @throws Exception
     */
    protected function evaluateBoolExpression()
    {
        $result = $this->evaluateMathBlock();
        while ($mathOp = $this->readChar(true)) {
            switch ($mathOp) {
                case '+':
                    $result += $this->evaluateMathBlock();
                    break;
                case '-':
                    $result -= $this->evaluateMathBlock();
                    break;
                case '=':
                    $nextChar = $this->readChar();
                    if ($nextChar == '=') {
                        $result = $result == $this->evaluateMathBlock();
                    } else {
                        throw new Exception('Unexpected token ' . $mathOp . $nextChar . '.');
                    }
                    break;
                case '!':
                    $nextChar = $this->readChar();
                    if ($nextChar == '=') {
                        $result = $result != $this->evaluateMathBlock();
                    } else {
                        throw new Exception('Unexpected token ' . $mathOp . $nextChar . '.');
                    }
                    break;
                case '>':
                    $nextChar = $this->readChar();
                    if ($nextChar == '=') {
                        $result = $result >= $this->evaluateMathBlock();
                    } else {
                        $this->unreadChar();
                        $result = $result > $this->evaluateMathBlock();
                    }
                    break;
                case '<':
                    $nextChar = $this->readChar();
                    if ($nextChar == '=') {
                        $result = $result <= $this->evaluateMathBlock();
                    } else {
                        $this->unreadChar();
                        $result = $result < $this->evaluateMathBlock();
                    }
                    break;
                case 'l': // check against regex
                    $nextChar = $this->readChar(true);
                    if ($nextChar == 'i') {
                        $nextChar = $this->readChar(true);
                        if ($nextChar == 'k') {
                            $nextChar = $this->readChar(true);
                            if ($nextChar == 'e') {
                                $result = preg_match('#' . $this->evaluateBoolExpression() . '#', $result);
                                break;
                            }
                        }
                    }
                    throw new Exception('Unexpected token ' . $mathOp . $nextChar . '.');
                break;
                // Lower lever operators
                case '&': // boolean "and" &&
                case '|': // boolean "or" ||
                case 'i': // find in set
                    // end of subexpression
                case ')':
                    // end of statement
                case ';':
                    // start of statement block
                    $this->unreadChar();
                    // return result from recursive call
                    return $result;
                    break;
                default:
                    throw new Exception('Unexpected token ' . $mathOp . '.');
                    break;
            }
        }
        return $result;
    }

    /**
     * One or more math boolean expression
     *
     * @return bool|int
     * @throws Exception
     */
    protected function evaluateBoolStatement()
    {
        /**
         * If expression contains only 1 math block - return as math expression result,
         * otherwise cast type of result to boolean
         */
        $result = $this->evaluateBoolExpression();
        while ($booleanOp = $this->readChar(true)) {
            switch ($booleanOp) {
                case '|':
                    $nextChar = $this->readChar();
                    if ($nextChar == '|') {
                        if ($result == true) {
                            // in order to reduce amount of calculations,
                            // skip the rest of expression and return result
                            $this->rewindUntil([';', ')'], '(');
                            $this->unreadChar();
                            return (bool) $result;
                        }
                        $result = (bool) ($result || $this->evaluateBoolExpression());
                    } else {
                        throw new Exception('Unexpected token ' . $booleanOp . $nextChar . '.');
                    }
                    break;
                case '&':
                    $nextChar = $this->readChar();
                    if ($nextChar == '&') {
                        if ($result == false) {
                            // in order to reduce amount of calculations,
                            // skip the rest of expression and return result
                            $this->rewindUntil([';', ')'], '(');
                            $this->unreadChar();
                            return (bool) $result;
                        }
                        $result = (bool) ($result && $this->evaluateBoolExpression());
                    } else {
                        throw new Exception('Unexpected token ' . $booleanOp . $nextChar . '.');
                    }
                    break;
                // end of subexpression
                case ')':
                    // end of statement
                case ';':
                    $this->unreadChar();
                    // return result from recursive call
                    return $result;
                    break;
                default:
                    throw new Exception('Unexpected token ' . $booleanOp . '.');
                    break;
            }
        }
        return $result;
    }

    /**
     * Rewind src until specified char is found
     *
     * @param $terminators
     * @param $nestedMarker
     */
    protected function rewindUntil($terminators = [], $nestedMarker = null)
    {
        $inSingleQuotedStr = false;
        $inDoubleQuotedStr = false;
        $depth = 0;

        $prevChar = null;
        while (!is_null($char = $this->readChar())) {
            if ($char == "'") {
                if (!$inSingleQuotedStr) {
                    if (!$inDoubleQuotedStr) {
                        $inSingleQuotedStr = true;
                    }
                } else if ($prevChar != "\\") {
                    $inSingleQuotedStr = false;
                }
            }

            if ($char == "\"") {
                if (!$inDoubleQuotedStr) {
                    if (!$inSingleQuotedStr) {
                        $inDoubleQuotedStr = true;
                    }
                } else if ($prevChar != "\\") {
                    $inDoubleQuotedStr = false;
                }
            }

            if (!$inSingleQuotedStr && !$inDoubleQuotedStr) {
                if ($nestedMarker && $char == $nestedMarker) {
                    $depth++;
                    $this->readChar();
                } else if (in_array($char, $terminators)) {
                    if ($depth == 0) {
                        break;
                    } else {
                        $depth--;
                    }
                }
            }

            $prevChar = $char;
        }
    }

    protected function skipBlockOrSingleStatement()
    {
        if (is_null($char = $this->readChar())) {
            // EOF is achieved
            return;
        }

        if ($char != '{') {
            // skip 1 statement
            $this->rewindUntil([';']);

        } else {
            // skip 1 code block
            $this->rewindUntil(['}'], '{');
        }

        // tell parser to start evaluating next statement
        $this->dynamicQueue[] = ';';
    }

    /**
     * Determine statement type and evaluate it
     *
     * @return void
     * @throws Exception
     */
    protected function evaluateStatement()
    {
        if (is_null($char = $this->readChar())) {
            // EOF is achieved
            return;
        }

        // handle braces
        if ($char == '{' || $char == '}') {
            $this->unreadChar();
            return;
        }

        $keyWord = null;
        // handle variable assignment statement
        if ($this->parseVariableName($char, $keyWord)) {
            // The place where we can implement accessing object methods and properties

            // RETURN STATEMENT
            if ($keyWord == self::STATEMENT_TYPE_RETURN) {
                $this->return = true;
                $this->evaluateStatement();
                return;
            }
            // END OF RETURN STATEMENT

            // IF STATEMENT
            if ($keyWord == self::STATEMENT_TYPE_IF) {
                $char = $this->readChar();
                if ($char != '(') {
                    throw new Exception('Unexpected token ' . $char . '.');
                }
                $scopePathKey = implode('_', $this->scopePath);
                $this->evaluateSubexpression(
                    $char, $this->lastIfResult[$scopePathKey]
                );
                if (!$this->lastIfResult[$scopePathKey]) {
                    $this->skipBlockOrSingleStatement();
                } else {
                    $this->dynamicQueue[] = ';';
                }
                return;
            }
            // END OF IF STATEMENT

            // ELSE STATEMENT
            if ($keyWord == self::STATEMENT_TYPE_ELSE) {
                $scopePathKey = implode('_', $this->scopePath);
                if (!isset($this->lastIfResult[$scopePathKey])) {
                    throw new Exception('Else statement should follow if statement.');
                }
                if ($this->lastIfResult[$scopePathKey]) {
                    // skip else if
                    if ($char = $this->readChar() == 'i') {
                        if ($nextChar = $this->readChar() == 'f') {
                            $this->rewindUntil([')']);
                        } else {
                            throw new Exception('Unexpected token ' . $nextChar . '.');
                        }
                    } else {
                        $this->unreadChar();
                        // else statement should be last case of the "if" construction
                        unset($this->lastIfResult[$scopePathKey]);
                    }
                    $this->skipBlockOrSingleStatement();
                } else {
                    if ($char = $this->readChar() == 'i') {
                        if ($nextChar = $this->readChar() == 'f') {
                            $this->unreadChar(2);
                        } else {
                            throw new Exception('Unexpected token ' . $nextChar . '.');
                        }
                    } else {
                        $this->unreadChar();
                        // else statement should be last case of the "if" construction
                        unset($this->lastIfResult[$scopePathKey]);
                    }
                    $this->dynamicQueue[] = ';';
                }
                return;
            }
            // END OF ELSE STATEMENT

            // ASSIGNMENT STATEMENT
            if (!is_null($char = $this->readChar())) {
                if ($char == '=') {
                    $nextChar = $this->readChar();
                    // equality operator
                    if ($nextChar == '=') {
                        // unread last 2 chars
                        $this->unreadChar(2);
                        // unread variable name
                        $this->unreadChar(strlen($keyWord));
                    } else {
                        // unread last char
                        $this->unreadChar();
                        // variable assignment
                        $this->evaluateStatement();
                        $this->var[$keyWord] = $this->lastResult;
                        return;
                    }
                } else {
                    // unread last char
                    $this->unreadChar();
                    // unread variable name
                    $this->unreadChar(strlen($keyWord));
                }
            } else {
                // unread variable name
                $this->unreadChar(strlen($keyWord));
            }
            // END OF ASSIGNMENT STATEMENT
        } else {
            $this->unreadChar();
        }

        $this->lastResult = $this->evaluateBoolStatement();
    }

    /**
     * Evaluate program statements one by one.
     * Statement can be variable assignment, return statement, boolean|math expression etc.
     *
     * @return mixed
     * @throws Exception
     */
    protected function evaluateStatements()
    {
        $this->evaluateStatement();
        while (!$this->return && $statementOp = $this->readChar()) {
            switch ($statementOp) {
                case '{':
                case '}':
                    $this->unreadChar();
                    return;
                    break;
                // end of statement
                case ';':
                    $this->evaluateStatement();
                    break;
                default:
                    throw new Exception('Unexpected token ' . $statementOp . '.');
                    break;
            }
        }
    }

    /**
     * Evaluate program statement blocks one by one.
     *
     * @param $code
     * @param $pos
     * @return mixed
     * @throws Exception
     */
    public function evaluate($code = '', $pos = 0)
    {
        $this->src = $code;
        $this->pos = $pos;

        $this->numOfOpenedBlocks = 0;
        $this->numOfClosedBlocks = 0;

        $this->scopePath[] = $this->numOfOpenedBlocks;

        $this->evaluateStatements();
        while (!$this->return && $separator = $this->readChar()) {
            switch ($separator) {
                // start of block
                case '{':
                    $this->numOfOpenedBlocks++;
                    $this->scopePath[] = $this->numOfOpenedBlocks;
                    $this->evaluateStatements();
                    break;
                // end of block
                case '}':
                    $this->numOfClosedBlocks++;
                    array_pop($this->scopePath);
                    $this->evaluateStatements();
                    break;
                default:
                    throw new Exception('Unexpected token ' . $separator . '.');
                break;
            }
        }

        if ($this->return) {
            return $this->lastResult;
        }

        if ($this->numOfOpenedBlocks != $this->numOfClosedBlocks) {
            throw new Exception('Wrong number of braces.');
        }
    }
}