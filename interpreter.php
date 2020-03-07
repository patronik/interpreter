<?php

class Interpreter
{
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

    protected function isSpace($str)
    {
        return in_array($str, [" ","\n","\t","\r"]);
    }

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

    protected function evaluateAtom()
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
            $atom = $this->evaluate();
            if ($this->readChar() != ')') {
                throw new Exception("Syntax error. Wrong number of parentheses.");
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

        // handle variable
        if (preg_match('#[a-zA-Z]#', $char)) {
            $varName = $char;

            // var name
            while ($char = $this->readChar(false, true)) {
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

            if ($char = $this->readChar()) {
                // Possible statements before atom
                if ($char == '=') {
                    $nextChar = $this->readChar();
                    // equality operator
                    if ($nextChar == '=') {
                        $this->unreadChar(2);
                    } else {
                        // variable assignment
                        $this->unreadChar();
                        $this->var[$varName] = $this->evaluate();
                        if ($this->readChar() != ';') {
                            throw new Exception('Syntax error. Wrong end of statement.');
                        }
                        // keep looking for atom
                        return $this->evaluateAtom();
                    }
                } else {
                    $this->unreadChar();
                }
            }

            if (!isset($this->var[$varName])) {
                throw new Exception("Variable {$varName} does not exist.");
            }

            $atom = $this->var[$varName];
        }

        // number
        if (preg_match('#[0-9]#', $char)) {
            $atom = $char;
            while ($char = $this->readChar()) {
                if (preg_match('#[0-9\.]#', $char)) {
                    $atom .= $char;
                    continue;
                }
                $this->unreadChar();
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
     * @return int
     */
    protected function evaluateBlock()
    {
        $result = $this->evaluateAtom();
        while ($delimiter = $this->readChar()) {
            switch ($delimiter) {
                case '*':
                    $result *= $this->evaluateAtom();
                    break;
                case '/':
                    $result /= $this->evaluateAtom();
                    break;
                case '%':
                    $result %= $this->evaluateAtom();
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
                    throw new Exception('Unexpected operator ' . $delimiter . '.');
                break;
            }
        }
        return $result;
    }

    protected function evaluateExpression()
    {
        $result = $this->evaluateBlock();

        while ($operator = $this->readChar()) {
            switch ($operator) {
                case '+':
                    $result += $this->evaluateBlock();
                    break;
                case '-':
                    $result -= $this->evaluateBlock();
                    break;
                case '=':
                    $nextChar = $this->readChar();
                    if ($nextChar == '=') {
                        $result = $result == $this->evaluateBlock();
                    } else {
                        throw new Exception('Unexpected operator ' . $operator . $nextChar . '.');
                    }
                    break;
                case '!':
                    $nextChar = $this->readChar();
                    if ($nextChar == '=') {
                        $result = $result != $this->evaluateBlock();
                    } else {
                        throw new Exception('Unexpected operator ' . $operator . $nextChar . '.');
                    }
                    break;
                case '>':
                    $nextChar = $this->readChar();
                    if ($nextChar == '=') {
                        $result = $result >= $this->evaluateBlock();
                    } else {
                        $this->unreadChar();
                        $result = $result > $this->evaluateBlock();
                    }
                    break;
                case '<':
                    $nextChar = $this->readChar();
                    if ($nextChar == '=') {
                        $result = $result <= $this->evaluateBlock();
                    } else {
                        $this->unreadChar();
                        $result = $result < $this->evaluateBlock();
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
                    throw new Exception('Unexpected operator ' . $operator . '.');
                    break;
            }
        }
        return $result;
    }

    /**
     * @param $code
     * @return mixed
     */
    public function evaluate($code = '')
    {
        if (!empty($code)) {
            $this->src = $code;
            $this->pos = 0;
        }

        $result = $this->evaluateExpression();
        while ($operator = $this->readChar()) {
            switch ($operator) {
                case '|':
                    $nextChar = $this->readChar();
                    if ($nextChar == '|') {
                        $nextResult = $this->evaluateExpression();
                        $result = $result || $nextResult;
                    } else {
                        throw new Exception('Unexpected operator ' . $operator . $nextChar . '.');
                    }
                    break;
                case '&':
                    $nextChar = $this->readChar();
                    if ($nextChar == '&') {
                        $nextResult = $this->evaluateExpression();
                        $result = $result && $nextResult;
                    } else {
                        throw new Exception('Unexpected operator ' . $operator . $nextChar . '.');
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
                    throw new Exception('Unexpected operator ' . $operator . '.');
                break;
            }
        }
        return $result;
    }
}