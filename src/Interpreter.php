<?php

namespace Vvoina\Zakerzon;

/**
 * Class Interpreter
 * @author Vasyl Voina <vasyl.voina@gmail.com>
 */

class Interpreter
{
    const STATEMENT_TYPE_SUB      = 'sub';
    const STATEMENT_TYPE_RETURN   = 'return';
    const STATEMENT_TYPE_IF       = 'if';
    const STATEMENT_TYPE_BREAK    = 'break';
    const STATEMENT_TYPE_FOR      = 'for';

    /**
     * Flag that shows whether return from script or function should be done
     *
     * @var bool
     */
    protected $return = false;

    /**
     * Breaks loop execution
     *
     * @var bool
     */
    protected $break = false;

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
     * Defined functions
     *
     * @var
     */
    protected $functions = [];

    /**
     * Variables
     *
     * @var array
     */
    protected $var = [];

    /**
     * Storage used by functions
     *
     * @var array
     */
    protected $stack = [];

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
        $storage =& $this->getStorageRef();
        $storage[$key] = $val;
    }

    /**
     * @param bool $val
     */
    public function setReturnLast(bool $val): void
    {
        $this->returnLast = $val;
    }

    /**
     * Returns a reference to variable storage
     *
     * @return array
     */
    protected function &getStorageRef()
    {
        if (count($this->stack) > 0) {
            return $this->stack[count($this->stack) - 1];
        }
        return $this->var;
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
     * @param $targetReference
     * @return int
     */
    protected function applyPreOperator($operator, $val, array &$targetReference = [])
    {
        switch ($operator) {
            case '-' :
                if ($targetReference['is_set']) {
                    $targetReference['ref'] = -$targetReference;
                }
                return -$val;
                break;
            case '+' :
                if ($targetReference['is_set']) {
                    $targetReference['ref'] = +$targetReference;
                }
                return +$val;
                break;
            case '++' :
                if ($targetReference['is_set']) {
                    ++$targetReference['ref'];
                }
                return ++$val;
                break;
            case '--' :
                if ($targetReference['is_set']) {
                    --$targetReference['ref'];
                }
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
                throw new \Exception('Syntax error. Wrong number of parentheses.');
            }
            return true;
        }
        return false;
    }

    /**
     * Parse array
     *
     * @param $char
     * @param $atom
     * @return bool
     * @throws Exception
     */
    protected function parseArrayAtom($char, &$atom)
    {
        if ($char != '[') {
            return false;
        }
        $array = [];
        $implicitKey = 0;
        do {
            $keyOrVal = $this->evaluateBoolExpression();
            $nextChar = $this->readChar();
            if (in_array($nextChar, [',',']'])) {
                $array[$implicitKey++] = $keyOrVal;
            } else if ($nextChar == '=') {
                $nextChar = $this->readChar();
                if ($nextChar == '>') {
                    $arrayVal = $this->evaluateBoolExpression();
                    if (!is_numeric($keyOrVal) && !is_string($keyOrVal)) {
                        throw new \Exception('Only string and integer array keys are supported.');
                    }
                    if (is_numeric($keyOrVal) && $keyOrVal >= $implicitKey) {
                        $implicitKey = $keyOrVal + 1;
                    }
                    $array[$keyOrVal] = $arrayVal;
                    $nextChar = $this->readChar();
                } else {
                    throw new \Exception('Unexpected token "' . $nextChar . '".');
                }
            }
        } while ($nextChar == ',');

        if ($nextChar != ']') {
            throw new \Exception('Unexpected token "' . $nextChar . '".');
        }

        $atom = $array;

        return true;
    }

    /**
     * @param $varName
     * @param $atom
     * @return bool
     * @throws Exception
     */
    protected function parseFunctionCallAtom($varName, &$atom)
    {
        // check if function exists
        if (!isset($this->functions[$varName])) {
            return false;
        }

        // function call left bracket
        $char = $this->readChar();
        if ($char != '(') {
            $this->unreadChar();
            return false;
        }

        $functionStack = [];
        $char = $this->readChar();
        if ($char != ')') {
            $this->unreadChar();
            // parse arguments
            $argPos = 0;
            do {
                if (isset($this->functions[$varName]['args'][$argPos])) {
                    $functionStack[$this->functions[$varName]['args'][$argPos]] = $this->evaluateBoolStatement();
                } else {
                    $functionStack[] = $this->evaluateBoolStatement();
                }
                $argPos++;
                $char = $this->readChar();
            } while ($char == ',');
        }

        if ($char != ')') {
            throw new \Exception('Unexpected token "' . $char . '".');
        }

        $this->stack[] = $functionStack;

        // save current state
        $prevPos = $this->pos;
        $prevRet = $this->return;
        $prevRes = $this->lastResult;
        $prevDynamicSrc = $this->dynamicSrc;

        $this->dynamicSrc = [];
        $this->pos = $this->functions[$varName]['pos'];
        $this->return = false;

        $atom = null;
        $this->evaluateBlockOrStatement();
        if ($this->return) {
            $atom = $this->lastResult;
        }
        array_pop($this->stack);

        // restore state
        $this->dynamicSrc = $prevDynamicSrc;
        $this->lastResult = $prevRes;
        $this->return = $prevRet;
        $this->pos = $prevPos;

        return true;
    }

    /**
     * @param $varName
     * @param $atom
     * @param array $targetReference
     * @return bool
     * @throws Exception
     */
    protected function parseArrayElementAtom($varName, &$atom, array &$targetReference = [])
    {
        // array element
        $char = $this->readChar();
        if ($char != '[') {
            $this->unreadChar();
            return false;
        }

        $elementKeys = [];
        do {
            $key = $this->evaluateBoolExpression();

            if (is_null($key)) {
                throw new \Exception('Failed to parse array key.');
            }
            if (!is_numeric($key) && !is_string($key)) {
                throw new \Exception('Only string and integer array keys are supported.');
            }

            $elementKeys[] = $key;

            $char = $this->readChar();
            if (is_null($char)) {
                throw new \Exception('Unexpected end of file.');
            }
            if ($char != ']') {
                throw new \Exception('Unexpected token "' . $char . '".');
            }

            $char = $this->readChar();
        } while ($char == '[');

        $this->unreadChar();

        $storage =& $this->getStorageRef();
        // initialize to empty array if not exists
        if (!isset($storage[$varName])) {
            $storage[$varName] = [];
        }
        $target =& $storage[$varName];
        foreach ($elementKeys as $key => $elementKey) {
            if ($key < (count($elementKeys) - 1)) {
                if (!isset($target[$elementKey])) {
                    $target[$elementKey] = [];
                }
            }
            $target =& $target[$elementKey];
        }

        $targetReference['is_set'] = true;
        $targetReference['ref'] =& $target;

        $atom = $target;
        return true;
    }

    /**
     * @param $char
     * @param $atom
     * @param array $targetReference
     * @return bool
     * @throws Exception
     */
    protected function parseVariableAtom($char, &$atom, array &$targetReference = [])
    {
        // variable
        $varName = null;
        if ($this->parseCharacterSequence($char, $varName)) {
            // try to process function call atom
            if ($this->parseFunctionCallAtom($varName, $atom)) {
                return true;
            }

            // try to parse array element
            if ($this->parseArrayElementAtom($varName, $atom, $targetReference)) {
                return true;
            }

            $storage =& $this->getStorageRef();
            // initialize to null if not exists
            if (!isset($storage[$varName])) {
                $storage[$varName] = null;
            }
            $target =& $storage[$varName];

            $targetReference['is_set'] = true;
            $targetReference['ref'] =& $target;

            $atom = $target;
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
        $hasDot = false;
        $asciiCode = ord($char);
        if ($asciiCode >= 48 && $asciiCode <= 57) { // 0-9
            $atom = $char;
            while (!is_null($char = $this->readChar(false, true))) {
                $asciiCode = ord($char);
                if ($asciiCode >= 48
                    && $asciiCode <= 57)  // 0-9
                {
                    $atom .= $char;
                    continue;
                } else if ($asciiCode == 46) { // .
                    if ($hasDot) {
                        throw new \Exception('Unexpected token "' . $char . '".');
                    }
                    $hasDot = true;
                    $atom .= $char;
                    continue;
                }
                if (!$this->isSpace($char)) {
                    $this->unreadChar();
                }
                break;
            }
            if ($asciiCode == 46) { // . at the end of number
                throw new \Exception('Unexpected token "' . $char . '".');
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
                throw new \Exception('Unexpected token "' . $nextChar . '".');
            }
        }
        $result = preg_match('#' . $this->evaluateBoolExpression() . '#', $result);
    }

    /**
     * The atomic (non dividable) part of expression
     *
     * @param array $targetReference
     * @return bool|int|mixed|string|null
     * @throws Exception
     */
    protected function parseAtom(array &$targetReference = [])
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

        if (!$this->parseVariableAtom($atomChar, $atom, $targetReference)) {
            if (!$this->parseArrayAtom($atomChar, $atom)) {
                if (!$this->parseNumberAtom($atomChar, $atom)) {
                    if (!$this->parseSingleQuotedStringAtom($atomChar, $atom)) {
                        $this->parseDoubleQuotedStringAtom($atomChar, $atom);
                    }
                }
            }
        }

        if ($preOperator) {
            $atom = $this->applyPreOperator($preOperator, $atom, $targetReference);
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
        $targetReference = ['is_set' => false, 'ref' => null];
        $result = $this->parseAtom($targetReference);
        while ($atomOp = $this->readChar(true)) {
            switch ($atomOp) {
                case '*':
                    $result *= $this->parseAtom($targetReference);
                break;
                case '/':
                    $result /= $this->parseAtom($targetReference);
                break;
                case '.':
                    $result .= $this->parseAtom($targetReference);
                break;
                case '%':
                    $result %= $this->parseAtom($targetReference);
                break;
                case '+':
                    $nextChar = $this->readChar();
                    if ($nextChar == '+') {
                        if (!$targetReference['is_set']) {
                            throw new \Exception('Assignment target is missing');
                        }
                        $targetReference['ref']++;
                    } else if ($nextChar == '=') {
                        if (!$targetReference['is_set']) {
                            throw new \Exception('Assignment target is missing');
                        }
                        $targetReference['ref'] = $targetReference['ref'] + $this->evaluateBoolStatement();
                        $result = $targetReference['ref'];
                    } else {
                        // Lower lever operator
                        $this->unreadChar(2);
                        return $result;
                    }
                break;
                case '-':
                    $nextChar = $this->readChar();
                    if ($nextChar == '-') {
                        if (!$targetReference['is_set']) {
                            throw new \Exception('Assignment target is missing');
                        }
                        $targetReference['ref']--;
                    } else if ($nextChar == '=') {
                        if (!$targetReference['is_set']) {
                            throw new \Exception('Assignment target is missing');
                        }
                        $targetReference['ref'] = $targetReference['ref'] - $this->evaluateBoolStatement();
                        $result = $targetReference['ref'];
                    } else {
                        // Lower lever operator
                        $this->unreadChar(2);
                        return $result;
                    }
                    break;
                case '=': // equality == or assignment or array key-val separator (=>)
                    $nextChar = $this->readChar();
                    if ($nextChar == '=') {
                        $this->unreadChar();
                        return $result;
                        break;
                    } elseif ($nextChar == '>')  {
                        $this->unreadChar(2);
                        return $result;
                        break;
                    } else {
                        $this->unreadChar();
                        // assign result to target (left side)
                        if (!$targetReference['is_set']) {
                            throw new \Exception('Assignment target is missing');
                        }
                        $targetReference['ref'] = $this->evaluateBoolStatement();
                        $result = $targetReference['ref'];
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
                // end of argument or statement
                case ',':
                // end of subexpression
                case ')':
                // end of statement
                case ';':
                // array value parsed
                case ']':
                    // start of statement block
                    $this->unreadChar();
                    return $result;
                    break;
                default:
                    throw new \Exception('Unexpected token "' . $atomOp . '".');
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
                    } elseif ($nextChar == '>')  {
                        $this->unreadChar(2);
                        return $result;
                        break;
                    } else {
                        throw new \Exception('Unexpected token "' . $mathOp . $nextChar . '".');
                    }
                break;
                case '!':
                    $nextChar = $this->readChar();
                    if ($nextChar == '=') {
                        $result = $result != $this->evaluateMathBlock();
                    } else {
                        throw new \Exception('Unexpected token "' . $mathOp . $nextChar . '".');
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
                case 'i': // find in set
                    $nextChar = $this->readChar(true);
                    if ($nextChar == 'n') {
                        $haystack = $this->evaluateMathBlock();
                        if (!is_array($haystack)) {
                            throw new \Exception('Haystack is not an array.');
                        }
                        $result = in_array($result, $haystack);
                    } else {
                        throw new \Exception('Unexpected token "' . $mathOp . $nextChar . '".');
                    }
                break;
                case 'l': // check against regex
                    $this->evaluateLikeExpression($result);
                break;
                // Lower lever operators
                case '&': // boolean "and" &&
                case '|': // boolean "or" ||
                // end of argument or statement
                case ',':
                // end of subexpression
                case ')':
                // end of statement
                case ';':                
                // array value parsed
                case ']':
                    $this->unreadChar();
                    // return result from recursive call
                    return $result;
                    break;
                default:
                    throw new \Exception('Unexpected token "' . $mathOp . '".');
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
                        throw new \Exception('Unexpected token "' . $booleanOp . $nextChar . '".');
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
                        throw new \Exception('Unexpected token "' . $booleanOp . $nextChar . '".');
                    }
                    break;
                // end of argument
                case ',':
                // end of subexpression
                case ')':
                // end of statement
                case ';':
                    $this->unreadChar();
                    // return result from recursive call
                    return $result;
                    break;
                default:
                    throw new \Exception('Unexpected token "' . $booleanOp . '".');
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
     * @param $stopOnBreak
     *
     * @throws Exception
     */
    protected function evaluateBlockOrStatement($stopOnBreak = false)
    {
        if (($char = $this->readChar()) != '{') {
            // evaluate 1 statement
            $this->unreadChar();
            $this->evaluateStatement();
            if (($char = $this->readChar()) != ';') {
                throw new \Exception('Unexpected token "' . $char . '".');
            }
        } else {
            $depth = 0;
            // evaluate 1 code block
            $this->evaluateStatement();
            while (!$this->return
                && (!$stopOnBreak || !$this->break)
                && $statementOp = $this->readChar()
            )
            {
                switch ($statementOp) {
                    case '{':
                        $depth++;
                    break;
                    case '}':
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
                        throw new \Exception('Unexpected token "' . $statementOp . '".');
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
     * Parse function
     */
    protected function parseFunction()
    {
        $char = $this->readChar();
        $functionName = null;
        if (!$this->parseCharacterSequence($char, $functionName)) {
            throw new \Exception('Failed to parse function name.');
        }

        if (isset($this->functions[$functionName])) {
            throw new \Exception('Function ' . $functionName . ' already exists.');
        }

        if (($char = $this->readChar()) != '(') {
            throw new \Exception('Unexpected token "' . $char . '".');
        }

        $parameters = [];
        do {
            $char = $this->readChar();
            // function without parameters
            if ($char == ')') {
                break;
            }
            $argName = null;
            if ($this->parseCharacterSequence($char, $argName)) {
                $parameters[] = $argName;
            } else {
                throw new \Exception('Unexpected token "' . $char . '".');
            }
            $char = $this->readChar();
        } while ($char == ',');

        if ($char != ')') {
            throw new \Exception('Unexpected token "' . $char . '".');
        }

        $this->functions[$functionName] = [
            'pos' => $this->pos,
            'args' => $parameters
        ];

        $this->skipBlockOrStatement();
    }

    /**
     * Execute for loop
     */
    protected function evaluateForLoop()
    {
        if (($char = $this->readChar()) != '(') {
            throw new \Exception('Unexpected token "' . $char . '".');
        }
        /**
         * Initializer statement
         */
        $this->evaluateStatement();

        if (($char = $this->readChar()) != ';') {
            throw new \Exception('Unexpected token "' . $char . '".');
        }

        $blockStartPos = null;
        $conditionPos = $this->pos;
        $afterStatementPos = null;

        do {
            $this->evaluateStatement();
            if (($char = $this->readChar()) != ';') {
                throw new \Exception('Unexpected token "' . $char . '".');
            }
            if ($this->lastResult) {
                if (is_null($afterStatementPos)) {
                    $afterStatementPos = $this->pos;
                }
                $this->rewindUntil([')'], '(');
                if (is_null($blockStartPos)) {
                    $blockStartPos = $this->pos;
                }
                $this->evaluateBlockOrStatement(true);
                if ($this->break || $this->return) {
                    break;
                }

                // Evaluate after statement
                $this->pos = $afterStatementPos;
                $this->evaluateStatement();

                // Prepare for next iteration
                $this->pos = $conditionPos;
            } else {
                break;
            }
        } while(true);

        $this->pos = $conditionPos;
        $this->rewindUntil([')'], '(');
        $this->skipBlockOrStatement();

        $this->break = false;
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
            throw new \Exception('Unexpected token "' . $char . '".');
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
                    throw new \Exception('Unexpected token "' . $nextChar . '".');
                }
            }
            if (($char = $this->readChar(true)) == 'i') {
                if ($nextChar = $this->readChar(true) != 'f') {
                    throw new \Exception('Unexpected token "' . $nextChar . '".');
                }
                if ($lastIfResult) {
                    if (($char = $this->readChar()) != '(') {
                        throw new \Exception('Unexpected token "' . $char . '".');
                    }
                    $this->rewindUntil([')'], '(');
                    $this->skipBlockOrStatement();
                } else {
                    if (($char = $this->readChar()) != '(') {
                        throw new \Exception('Unexpected token "' . $char . '".');
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
                    throw new \Exception('Only 1 else statement can be used after if.');
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

            // FUNCTION DEFINITION
            if ($keyWord == self::STATEMENT_TYPE_SUB) {
                $this->parseFunction();
                $this->dynamicSrc[] = ';';
                return;
            }
            // FUNCTION DEFINITION

            // RETURN STATEMENT
            if ($keyWord == self::STATEMENT_TYPE_RETURN) {
                $this->return = true;
                $this->evaluateStatement();
                return;
            }
            // END OF RETURN STATEMENT

            // BREAK LOOP STATEMENT
            if ($keyWord == self::STATEMENT_TYPE_BREAK) {
                $this->break = true;
                return;
            }
            // END OF BREAK LOOP STATEMENT

            // IF STATEMENT
            if ($keyWord == self::STATEMENT_TYPE_IF) {
                $this->evaluateIfStructure();
                if (!$this->return) {
                    $this->dynamicSrc[] = ';';
                }
                return;
            }
            // END OF IF STATEMENT

            // FOR STATEMENT
            if ($keyWord == self::STATEMENT_TYPE_FOR) {
                $this->evaluateForLoop();
                if (!$this->return) {
                    $this->dynamicSrc[] = ';';
                }
                return;
            }
            // END OF FOR STATEMENT

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
                case ',':
                case ';':
                    $this->evaluateStatement();
                    break;
                default:
                    throw new \Exception('Unexpected token "' . $statementOp . '".');
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

        $this->evaluateStatements();
        while (!$this->return && $separator = $this->readChar()) {
            switch ($separator) {
                // start of block
                case '{':
                    $this->evaluateStatements();
                    break;
                // end of block
                case '}':
                    $this->evaluateStatements();
                    break;
                default:
                    throw new \Exception('Unexpected token "' . $separator . '".');
                break;
            }
        }

        if ($this->return) {
            return $this->lastResult;
        }

        if ($this->returnLast) {
            return $this->lastResult;
        }
    }
}