<?php

/**
 * Class Interpreter
 * @author Vasyl Voina <vasyl.voina@gmail.com>
 */

class Interpreter
{
    const STATEMENT_TYPE_ASSIGN    = 'assignment';
    const STATEMENT_TYPE_BOOL_EXPR = 'bool_expr';
    const STATEMENT_TYPE_RETURN    = 'return';

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

        $char = $this->readChar();

        if (empty($char)) {
            return $atom;
        }

        // handle subexpression
        if ($char == '(') {
            $atom = $this->evaluateBoolStatement();
            if ($this->readChar() != ')') {
                throw new Exception('Syntax error. Wrong number of parentheses.');
            }
            return $atom;
        }

        // check for boolean inversion
        if ($char == '!') {
            $boolInversion = true;
            $char = $this->readChar();
        }

        // unary plus and pre decrement
        if ($char == '+') {
            $preOperator = $char;
            $char = $this->readChar();
            // check for pre increment
            if ($char == '+') {
                $preOperator .= $char;
                $char = $this->readChar();
            }
        }

        // unary minus and pre decrement
        if ($char == '-') {
            $preOperator = $char;
            $char = $this->readChar();
            // check for pre decrement
            if ($char == '-') {
                $preOperator .= $char;
                $char = $this->readChar();
            }
        }

        // variable
        if (preg_match('#[a-zA-Z]#', $char)) {
            $varName = $char;

            // var name
            while (!is_null($char = $this->readChar(false, true))) {
                if (preg_match('#[a-zA-Z0-9_]#', $char)) {
                    $varName .= $char;
                    continue;
                }
                if (!$this->isSpace($char)) {
                    $this->unreadChar();
                }
                break;
            }

            // The place where we can implement accessing object methods and properties

            if (!isset($this->var[$varName])) {
                throw new Exception('Variable ' . $varName . ' does not exist.');
            }

            $atom = $this->var[$varName];
        }

        // number
        if (preg_match('#[0-9]#', $char)) {
            $atom = $char;
            while (!is_null($char = $this->readChar())) {
                if (preg_match('#[0-9\.]#', $char)) {
                    $atom .= $char;
                    continue;
                }
                $this->unreadChar();
                break;
            }
        }

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
        }

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
        while ($separator = $this->readChar()) {
            switch ($separator) {
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
                    $this->unreadChar();
                    return $result;
                break;
                default:
                    throw new Exception('Unexpected operator ' . $separator . '.');
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
        while ($separator = $this->readChar()) {
            switch ($separator) {
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
                        throw new Exception('Unexpected operator ' . $separator . $nextChar . '.');
                    }
                    break;
                case '!':
                    $nextChar = $this->readChar();
                    if ($nextChar == '=') {
                        $result = $result != $this->evaluateMathBlock();
                    } else {
                        throw new Exception('Unexpected operator ' . $separator . $nextChar . '.');
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
                    $this->unreadChar();
                    // return result from recursive call
                    return $result;
                break;
                default:
                    throw new Exception('Unexpected operator ' . $separator . '.');
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
        $result = $this->evaluateBoolExpression();
        while ($separator = $this->readChar()) {
            switch ($separator) {
                case '|':
                    $nextChar = $this->readChar();
                    if ($nextChar == '|') {
                        $nextResult = $this->evaluateBoolExpression();
                        $result = $result || $nextResult;
                    } else {
                        throw new Exception('Unexpected operator ' . $separator . $nextChar . '.');
                    }
                break;
                case '&':
                    $nextChar = $this->readChar();
                    if ($nextChar == '&') {
                        $nextResult = $this->evaluateBoolExpression();
                        $result = $result && $nextResult;
                    } else {
                        throw new Exception('Unexpected operator ' . $separator . $nextChar . '.');
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
                    throw new Exception('Unexpected operator ' . $separator . '.');
                break;
            }
        }
        return $result;
    }

    /**
     * Determine statement type and evaluate it
     *
     * @return array
     * @throws Exception
     */
    protected function evaluateStatement()
    {
        $char = $this->readChar();
        // handle variable assignment statement
        if (preg_match('#[a-zA-Z]#', $char)) {
            $keyWord = $char;
            // var name
            while (!is_null($char = $this->readChar(false, true))) {
                if (preg_match('#[a-zA-Z0-9_]#', $char)) {
                    $keyWord .= $char;
                    continue;
                }
                if (!$this->isSpace($char)) {
                    $this->unreadChar();
                }
                break;
            }

            // The place where we can implement accessing object methods and properties

            // RETURN STATEMENT
            if ($keyWord == self::STATEMENT_TYPE_RETURN) {
                return [self::STATEMENT_TYPE_RETURN, $this->evaluateBoolStatement()];
            }
            // END OF RETURN STATEMENT

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
                        $this->var[$keyWord] = $this->evaluateBoolStatement();
                        return [self::STATEMENT_TYPE_ASSIGN, $this->var[$keyWord]];
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

        return [self::STATEMENT_TYPE_BOOL_EXPR, $this->evaluateBoolStatement()];
    }

    /**
     * Evaluate program statements one by one.
     * Statement can be variable assignment, return statement, boolean|math expression etc.
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

        $this->evaluateStatement();
        while ($separator = $this->readChar()) {
            switch ($separator) {
                // TODO implement functions
                /**
                case '}':
                    $this->unreadChar();
                    return;
                break;
                **/
                case ';':
                    list($statementType, $statementResult) = $this->evaluateStatement();
                    // return value from program
                    if ($statementType == self::STATEMENT_TYPE_RETURN) {
                        return $statementResult;
                    }
                break;
                default:
                    throw new Exception('Unexpected operator ' . $separator . '.');
                break;
            }
        }

        // enforce semicolon in the end of last statement
        $this->unreadChar();
        if ($this->readChar() != ';') {
            throw new Exception('Unexpected end of file.');
        }
    }
}