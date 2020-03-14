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
    protected $lastIfResult;

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

        // handle subexpression
        if ($this->evaluateSubexpression($atomChar, $atom)) {
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
        while ($atomOp = $this->readChar()) {
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
                case '=':
                case '!':
                case '>':
                case '<':
                case '&':
                case '|':
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
        while ($mathOp = $this->readChar()) {
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
                // Lower lever operators
                case '&':
                case '|':
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
        while ($booleanOp = $this->readChar()) {
            switch ($booleanOp) {
                case '|':
                    $nextChar = $this->readChar();
                    if ($nextChar == '|') {
                        if ($result == true) {
                            // in order to reduce amount of calculations,
                            // skip the rest of expression and return result
                            while (!is_null($char = $this->readChar())) {
                                if ($char == ';' || $char == ')') {
                                    $this->unreadChar();
                                    break;
                                }
                            }
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
                            while (!is_null($char = $this->readChar())) {
                                if ($char == ';' || $char == ')') {
                                    $this->unreadChar();
                                    break;
                                }
                            }
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

    protected function execBlockOrSingleStatement()
    {
        if (is_null($char = $this->readChar())) {
            // EOF is achieved
            return;
        }

        if ($char != '{') {
            $this->unreadChar();
            // evaluate 1 statement
            $this->evaluateStatement();
            return;
        } else {
            $this->numOfOpenedBlocks++;
            $this->evaluateStatements();
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
            while (!is_null($char = $this->readChar())) {
                if ($char == ';') {
                    $this->unreadChar();
                    break;
                }
            }
            return;
        }

        $this->numOfOpenedBlocks++;

        $inSingleQuotedStr = false;
        $inDoubleQuotedStr = false;

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

            if ($char == '}'
                && !$inSingleQuotedStr
                && !$inDoubleQuotedStr
                )
            {
                $this->unreadChar();
                break;
            }
            $prevChar = $char;
        }
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
                $this->evaluateSubexpression($char = $this->readChar(), $this->lastIfResult);
                if ($this->lastIfResult) {
                    $this->execBlockOrSingleStatement();
                } else {
                    $this->skipBlockOrSingleStatement();
                }
                return;
            }
            // END OF IF STATEMENT

            // ELSE STATEMENT
            if ($keyWord == self::STATEMENT_TYPE_ELSE) {
                if (!$this->lastIfResult) {
                    $this->execBlockOrSingleStatement();
                } else {
                    $this->skipBlockOrSingleStatement();
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

        $this->evaluateStatements();
        while (!$this->return && $separator = $this->readChar()) {
            switch ($separator) {
                // start of block
                case '{':
                    $this->numOfOpenedBlocks++;
                    $this->evaluateStatements();
                    break;
                // end of block
                case '}':
                    $this->numOfClosedBlocks++;
                    $this->evaluateStatements();
                    break;
                default:
                    throw new Exception('Unexpected token ' . $separator . '.');
                break;
            }
        }

        if (!$this->return) {
            if ($this->numOfOpenedBlocks != $this->numOfClosedBlocks) {
                throw new Exception('Wrong number of braces.');
            }
        }

        return $this->lastResult;
    }
}