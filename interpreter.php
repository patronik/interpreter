<?php

/**
 * Class Interpreter
 * @author Vasyl Voina <vasyl.voina@gmail.com>
 */

class Interpreter
{
    const STATEMENT_TYPE_RETURN = 'return';
    const STATEMENT_TYPE_IF     = 'if';

    protected $return = false;

    protected $numOfOpenedBlocks = 0;
    protected $numOfClosedBlocks = 0;

    /**
     * Holds the reference to last variable
     * @var
     */
    protected $assignmentTarget;

    /**
     * Result of last executed statement
     *
     * @var
     */
    protected $lastResult;

    /**
     * Flag determines whether last result will be returned from the program
     * @var
     */
    protected $returnLast;

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
    protected $dynamicSrc = [];

    /**
     * @param string $key
     * @param mixed $val
     */
    public function setVar($key, $val): void
    {
        $this->var[$key] = $val;
    }

    /**
     * @param bool $val
     */
    public function setReturnLast(bool $val): void
    {
        $this->returnLast = $val;
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
        if (count($this->dynamicSrc)) {
            return array_shift($this->dynamicSrc);
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
    protected function parseCharacterSequence($char, &$varName)
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
     * Parse target array element key for assignment
     *
     * @param array $keys
     * @throws Exception
     */
    protected function parseArrayElementPath(&$keys = [])
    {
        $char = $this->readChar();
        if ($char != '[') {
            $this->unreadChar();
            return;
        }

        do {
            $key = $this->parseMathAtom();

            if (is_null($key)) {
                throw new Exception('Failed to parse array key.');
            }
            if (!is_int($key) && !is_string($key)) {
                throw new Exception('Only string and integer array keys are supported.');
            }

            $keys[] = $key;

            $char = $this->readChar();
            if (is_null($char)) {
                throw new Exception('Unexpected end of file.');
            }
            if ($char != ']') {
                throw new Exception('Unexpected token ' . $char . '.');
            }

            $char = $this->readChar();
        } while ($char == '[');

        $this->unreadChar();
    }

    protected function parseArrayAtom($char, &$atom)
    {
        if ($char != '[') {
            return false;
        }
        $array = [];
        $implicitKey = 0;
        do {
            $keyOrVal = $this->parseMathAtom();
            $nextChar = $this->readChar();
            if (in_array($nextChar, [',',']'])) {
                $array[$implicitKey++] = $keyOrVal;
            } else if ($nextChar == '>') {
                $arrayVal = $this->parseMathAtom();
                if (is_numeric($keyOrVal) && $keyOrVal >= $implicitKey) {
                    $implicitKey = $keyOrVal + 1;
                }
                $array[$keyOrVal] = $arrayVal;
                $nextChar = $this->readChar();
            }
        } while ($nextChar == ',');

        if ($nextChar != ']') {
            throw new Exception('Unexpected token ' . $nextChar . '.');
        }

        $atom = $array;

        return true;
    }

    /**
     * @param $char
     * @param $atom
     * @return bool
     * @throws Exception
     */
    protected function parseVariableAtom($char, &$atom)
    {
        // variable|array element
        $varName = null;
        if ($this->parseCharacterSequence($char, $varName)) {
            $arrayElementKeys = [];
            $this->parseArrayElementPath($arrayElementKeys);

            if (count($arrayElementKeys)) {
                // initialize to empty array if not exists
                if (!isset($this->var[$varName])) {
                    $this->var[$varName] = [];
                }
                $this->assignmentTarget =& $this->var[$varName];
                foreach ($arrayElementKeys as $key => $arrayElementKey) {
                    if ($key < (count($arrayElementKeys) - 1)) {
                        if (!isset($this->assignmentTarget[$arrayElementKey])) {
                            $this->assignmentTarget[$arrayElementKey] = [];
                        }
                    }
                    $this->assignmentTarget =& $this->assignmentTarget[$arrayElementKey];
                }
            } else {
                // initialize to null if not exists
                if (!isset($this->var[$varName])) {
                    $this->var[$varName] = null;
                }
                $this->assignmentTarget =& $this->var[$varName];
            }

            $atom = $this->assignmentTarget;
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
    protected function parseSingleQuotedStringAtom($char, &$atom)
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
    protected function parseDoubleQuotedStringAtom($char, &$atom)
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
    protected function parseNumberAtom($char, &$atom)
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
     * Like operator is called, check result against regular expression
     *
     * @param $result
     * @throws Exception
     */
    protected function evaluateLikeExpression(&$result)
    {
        foreach (['i', 'k', 'e'] as $char) {
            $nextChar = $this->readChar(true);
            if ($nextChar != $char) {
                throw new Exception('Unexpected token ' . $nextChar . '.');
            }
        }
        $result = preg_match('#' . $this->evaluateMathBlock() . '#', $result);
    }

    /**
     * The atomic (indivisible) part of math expression
     *
     * @return bool|int|mixed|string|null
     * @throws Exception
     */
    protected function parseMathAtom()
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

        if (!$this->parseArrayAtom($atomChar, $atom)) {
            if (!$this->parseVariableAtom($atomChar, $atom)) {
                if (!$this->parseNumberAtom($atomChar, $atom)) {
                    if (!$this->parseSingleQuotedStringAtom($atomChar, $atom)) {
                        $this->parseDoubleQuotedStringAtom($atomChar, $atom);
                    }
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
        $result = $this->parseMathAtom();
        while ($atomOp = $this->readChar(true)) {
            switch ($atomOp) {
                case '*':
                    $result *= $this->parseMathAtom();
                break;
                case '/':
                    $result /= $this->parseMathAtom();
                break;
                case '.':
                    $result .= $this->parseMathAtom();
                break;
                case '%':
                    $result %= $this->parseMathAtom();
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
                case '=': // equality == or assignment
                    $nextChar = $this->readChar();
                    if ($nextChar == '=') {
                        $this->unreadChar();
                        return $result;
                        break;
                    } else {
                        $this->unreadChar();
                        // assign result to target (left side)
                        $this->assignmentTarget = $this->evaluateBoolStatement();
                        $result = $this->assignmentTarget;
                    }
                    break;
                // Lower lever operators
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
                    $this->evaluateLikeExpression($result);
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

    /**
     * Evaluate block or statement and read terminator symbol
     *
     * @throws Exception
     */
    protected function evaluateBlockOrStatement()
    {

        if (($char = $this->readChar()) != '{') {
            // evaluate 1 statement
            $this->unreadChar();
            $this->evaluateStatement();
            if (($char = $this->readChar()) != ';') {
                throw new Exception('Unexpected token ' . $char . '.');
            }
        } else {
            $this->numOfOpenedBlocks++;
            $depth = 0;
            // evaluate 1 code block
            $this->evaluateStatement();
            while (!$this->return && $statementOp = $this->readChar()) {
                switch ($statementOp) {
                    case '{':
                        $this->numOfOpenedBlocks++;
                        $depth++;
                    break;
                    case '}':
                        $this->numOfClosedBlocks++;
                        if ($depth == 0) {
                            return;
                        }
                        $depth--;
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
    }

    /**
     * Skip block or statement including terminator symbol
     *
     * @throws Exception
     */
    protected function skipBlockOrStatement()
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
    }

    /**
     * Evaluate if structure
     *
     * @throws Exception
     */
    protected function evaluateIfStructure()
    {
        $lastIfResult = null;
        if (($char = $this->readChar()) != '(') {
            throw new Exception('Unexpected token ' . $char . '.');
        }
        $this->evaluateSubexpression($char, $lastIfResult);

        if ($lastIfResult) {
            $this->evaluateBlockOrStatement();
            if ($this->return) {
                return;
            }
        } else {
            $this->skipBlockOrStatement();
        }

        $elseFound = false;
        while (!$this->return) {
            if (($char = $this->readChar(true)) != 'e') {
                if (!is_null($char)) {
                    $this->unreadChar();
                }
                break;
            }
            foreach (['l', 's', 'e'] as $char) {
                $nextChar = $this->readChar(true);
                if ($nextChar != $char) {
                    throw new Exception('Unexpected token ' . $nextChar . '.');
                }
            }
            if (($char = $this->readChar(true)) == 'i') {
                if ($nextChar = $this->readChar(true) != 'f') {
                    throw new Exception('Unexpected token ' . $nextChar . '.');
                }
                if ($lastIfResult) {
                    if (($char = $this->readChar()) != '(') {
                        throw new Exception('Unexpected token ' . $char . '.');
                    }
                    $this->rewindUntil([')'], '(');
                    $this->skipBlockOrStatement();
                } else {
                    if (($char = $this->readChar()) != '(') {
                        throw new Exception('Unexpected token ' . $char . '.');
                    }
                    $this->evaluateSubexpression($char, $lastIfResult);
                    if ($lastIfResult) {
                        $this->evaluateBlockOrStatement();
                    } else {
                        $this->skipBlockOrStatement();
                    }
                }
                continue;
            } else {
                $this->unreadChar();
                if ($elseFound) {
                    throw new Exception('Only 1 else statement can be used after if.');
                }
                $elseFound = true;
                if ($lastIfResult) {
                    $this->skipBlockOrStatement();
                } else {
                    $this->evaluateBlockOrStatement();
                }
            }
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
        // handle statements with preceding keywords
        if ($this->parseCharacterSequence($char, $keyWord)) {
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
                $this->evaluateIfStructure();
                $this->dynamicSrc[] = ';';
                return;
            }
            // END OF IF STATEMENT

            // unread keyword
            $this->unreadChar(strlen($keyWord));
        } else {
            $this->unreadChar();
        }

        $this->lastResult = $this->evaluateBoolStatement();
    }

    /**
     * Evaluate statements one by one.
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

        if ($this->return) {
            return $this->lastResult;
        }

        if ($this->numOfOpenedBlocks != $this->numOfClosedBlocks) {
            throw new Exception('Wrong number of braces.');
        }

        if ($this->returnLast) {
            return $this->lastResult;
        }
    }
}