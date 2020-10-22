<?php

namespace Vvoina\Zakerzon;

/**
 * Class Atom
 * @author Vasyl Voina <vasyl.voina@gmail.com>
 */

class Atom
{
    const TYPE_INT    = 'int';
    const TYPE_DOUBLE = 'double';
    const TYPE_STRING = 'string';
    const TYPE_ARRAY  = 'array';

    protected $type;

    protected $intVal;
    protected $doubleVal;
    protected $stringVal;
    protected $arrayVal;

    protected $varRef;
    protected $varSet;

    protected function clearVal()
    {
        switch ($this->type) {
            case self::TYPE_INT :
                $this->intVal = null;
            break;
            case self::TYPE_DOUBLE :
                $this->doubleVal = null;
            break;
            case self::TYPE_STRING :
                $this->stringVal = null;
            break;
            case self::TYPE_ARRAY :
                $this->arrayVal = null;
            break;
        }
    }

    public function getType()
    {
        return $this->type;
    }

    public function setInt($val) 
    {
        $this->clearVal();
        $this->intVal = $val;
        $this->type = self::TYPE_INT;
    }

    public function setDouble($val) {
        $this->clearVal();
        $this->doubleVal = $val;
        $this->type = self::TYPE_DOUBLE;
    }

    public function setString($val) 
    {
        $this->clearVal();
        $this->stringVal = $val;
        $this->type = self::TYPE_STRING;
    }

    public function setArray($val) 
    {
        $this->clearVal();
        $this->arrayVal = $val;
        $this->type = self::TYPE_ARRAY;
    }

    /**
     * @return int
     */
    public function getInt() 
    {
        return $this->intVal;
    }

    /**
     * @return double
     */
    public function getDouble() 
    {
        return $this->doubleVal;
    }

    /**
     * @return string
     */
    public function getString() 
    {
        return $this->stringVal;
    }

    /**
     * @return array
     */
    public function getArray() 
    {
        return $this->arrayVal;
    }

    public function setVarRef(&$ref) 
    {
        $this->varRef = $ref;
        $this->varSet = true;
    }

    /**
     * @return 
     */
    public function &getVarRef() 
    {
        return $this->varRef;        
    }

    /**
     * @return bool|null
     */
    public function getVarSet() 
    {
        return $this->varSet;
    }
}