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
    const TYPE_BOOL   = 'bool';
    const TYPE_NOOL   = 'null';

    protected $type;

    protected $intVal;
    protected $doubleVal;
    protected $stringVal;
    protected $arrayVal;
    protected $boolVal;
    protected $noolVal;

    public function __construct($type = null, $val = null)
    {
      if ($type && $val) {
        switch ($type) {
          case self::TYPE_INT :
              $this->type = self::TYPE_INT;
              $this->intVal = $val;
          break;
          case self::TYPE_DOUBLE :
              $this->type = self::TYPE_DOUBLE;
              $this->doubleVal = $val;
          break;
          case self::TYPE_STRING :
            $this->type = self::TYPE_STRING;
              $this->stringVal = $val;
          break;
          case self::TYPE_ARRAY :
            $this->type = self::TYPE_ARRAY;
              $this->arrayVal = $val;
          break;
          case self::TYPE_BOOL :
            $this->type = self::TYPE_BOOL;
            $this->boolVal = $val;
          break;
          case self::TYPE_NOOL :
            $this->type = self::TYPE_NOOL;
            $this->noolVal = $val;
          break;
          default :
            throw new \Exception('Not supported atom type ' . $type);
          break;
        }
      } else {
        $this->type = self::TYPE_NOOL;
        $this->noolVal = null;
      }
    }

    protected static $joiners = array (
      'int' => 
      array (
        'int' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jint\\Jint',
          'instance' => NULL,
        ),
        'double' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jint\\Jdouble',
          'instance' => NULL,
        ),
        'string' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jint\\Jstring',
          'instance' => NULL,
        ),
        'array' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jint\\Jarray',
          'instance' => NULL,
        ),
        'bool' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jint\\Jbool',
          'instance' => NULL,
        ),
        'null' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jint\\Jnull',
          'instance' => NULL,
        ),
      ),
      'double' => 
      array (
        'int' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jdouble\\Jint',
          'instance' => NULL,
        ),
        'double' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jdouble\\Jdouble',
          'instance' => NULL,
        ),
        'string' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jdouble\\Jstring',
          'instance' => NULL,
        ),
        'array' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jdouble\\Jarray',
          'instance' => NULL,
        ),
        'bool' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jdouble\\Jbool',
          'instance' => NULL,
        ),
        'null' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jdouble\\Jnull',
          'instance' => NULL,
        ),
      ),
      'string' => 
      array (
        'int' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jstring\\Jint',
          'instance' => NULL,
        ),
        'double' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jstring\\Jdouble',
          'instance' => NULL,
        ),
        'string' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jstring\\Jstring',
          'instance' => NULL,
        ),
        'array' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jstring\\Jarray',
          'instance' => NULL,
        ),
        'bool' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jstring\\Jbool',
          'instance' => NULL,
        ),
        'null' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jstring\\Jnull',
          'instance' => NULL,
        ),
      ),
      'array' => 
      array (
        'int' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jarray\\Jint',
          'instance' => NULL,
        ),
        'double' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jarray\\Jdouble',
          'instance' => NULL,
        ),
        'string' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jarray\\Jstring',
          'instance' => NULL,
        ),
        'array' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jarray\\Jarray',
          'instance' => NULL,
        ),
        'bool' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jarray\\Jbool',
          'instance' => NULL,
        ),
        'null' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jarray\\Jnull',
          'instance' => NULL,
        ),
      ),
      'bool' => 
      array (
        'int' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jbool\\Jint',
          'instance' => NULL,
        ),
        'double' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jbool\\Jdouble',
          'instance' => NULL,
        ),
        'string' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jbool\\Jstring',
          'instance' => NULL,
        ),
        'array' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jbool\\Jarray',
          'instance' => NULL,
        ),
        'bool' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jbool\\Jbool',
          'instance' => NULL,
        ),
        'null' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jbool\\Jnull',
          'instance' => NULL,
        ),
      ),
      'null' => 
      array (
        'int' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jnull\\Jint',
          'instance' => NULL,
        ),
        'double' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jnull\\Jdouble',
          'instance' => NULL,
        ),
        'string' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jnull\\Jstring',
          'instance' => NULL,
        ),
        'array' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jnull\\Jarray',
          'instance' => NULL,
        ),
        'bool' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jnull\\Jbool',
          'instance' => NULL,
        ),
        'null' => 
        array (
          'class' => 'Vvoina\\Zakerzon\\Atom\\Jnull\\Jnull',
          'instance' => NULL,
        ),
      ),
    );

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
            case self::TYPE_BOOL :
              $this->boolVal = null;
            break;
            case self::TYPE_NOOL :
              $this->noolVal = null;
            break;
        }
    }

    /**
     * @return Joiner
     */
    protected function getJoiner(Atom $right)
    {
        if (!isset(self::$joiners[$this->type][$right->getType()])) {
            throw new \Exception('Not supported atom types');
        }
        if (!isset(self::$joiners[$this->type][$right->getType()]['instance'])) {
            if (!class_exists(self::$joiners[$this->type][$right->getType()]['class'])) {
              throw new \Exception(
                  sprintf('Joiner class %s does not exist',
                  self::$joiners[$this->type][$right->getType()]['class']
                )
              );
            }
            self::$joiners[$this->type][$right->getType()]['instance'] 
            = new self::$joiners[$this->type][$right->getType()]['class']();
        }
        return self::$joiners[$this->type][$right->getType()]['instance'];
    }

    public function join($operator, Atom $right)
    {
        $this->getJoiner($right)->join($operator, $this, $right);
        return $this;
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

    public function setBool($val) 
    {
        $this->clearVal();
        $this->boolVal = $val;
        $this->type = self::TYPE_BOOL;
    }

    public function setNool($val) 
    {
        $this->clearVal();
        $this->boolVal = $val;
        $this->type = self::TYPE_NOOL;
    }

    /**
     * @return int
     */
    public function getInt() 
    {
        if ($this->type != self::TYPE_INT) {
            throw new \Exception('Wrong atom type');
        }
        return $this->intVal;
    }

    /**
     * @return double
     */
    public function getDouble() 
    {
        if ($this->type != self::TYPE_DOUBLE) {
            throw new \Exception('Wrong atom type');
        }
        return $this->doubleVal;
    }

    /**
     * @return string
     */
    public function getString() 
    {
        if ($this->type != self::TYPE_STRING) {
            throw new \Exception('Wrong atom type');
        }
        return $this->stringVal;
    }

    /**
     * @return array
     */
    public function getArray() 
    {
        if ($this->type != self::TYPE_ARRAY) {
            throw new \Exception('Wrong atom type');
        }
        return $this->arrayVal;
    }  
    
    /**
     * @return array
     */
    public function getBool() 
    {
        if ($this->type != self::TYPE_BOOL) {
            throw new \Exception('Wrong atom type');
        }
        return $this->boolVal;
    }

    public function getNool() 
    {
      if ($this->type != self::TYPE_NOOL) {
        throw new \Exception('Wrong atom type');
      }
      return $this->noolVal;
    }    

    public function preOperator($operator)
    {
        switch ($operator) {
          case '++':
            switch ($this->type) {
              case self::TYPE_INT:
                ++$this->intVal;
              break;
              case self::TYPE_DOUBLE:
                ++$this->doubleVal;
              break;
              default:
                throw new \Exception('Pre increment is not supported by type ' . $this->type);
              break;
            }
          break;
          case '--':
            switch ($this->type) {
              case self::TYPE_INT:                
                --$this->intVal;
              break;
              case self::TYPE_DOUBLE:                
                --$this->doubleVal;
              break;
              default:
                throw new \Exception('Pre decrement is not supported by type ' . $this->type);
              break;
            }
          break;
          case '!':
            switch ($this->type) {
              case self::TYPE_INT:                
                $this->setBool(!$this->getInt());
              break;
              case self::TYPE_DOUBLE:
                $this->setBool(!$this->getDouble());
              break;
              case self::TYPE_STRING:
                $this->setBool(!$this->getString());
              break;
              case self::TYPE_BOOL:
                $this->setBool(!$this->getBool());
              break;
              default:
                throw new \Exception('Boolean inversion is not supported by type ' . $this->type);
              break;
            }
          break;
          default:
            throw new \Exception('Not supported pre operator ' . $operator);
          break;
        }        
    }

    public function postOperator($operator)
    {
        switch ($operator) {
          case '++':
            switch ($this->type) {
              case self::TYPE_INT:
                $this->intVal++;
              break;
              case self::TYPE_DOUBLE:
                $this->doubleVal++;
              break;
              default:
                throw new \Exception('Post increment is not supported by type ' . $this->type);
              break;
            }
          break;
          case '--':
            switch ($this->type) {
              case self::TYPE_INT:                
                $this->intVal--;
              break;
              case self::TYPE_DOUBLE:                
                $this->doubleVal--;
              break;
              default:
                throw new \Exception('Post decrement is not supported by type ' . $this->type);
              break;
            }
          break;          
          default:
            throw new \Exception('Not supported post operator ' . $operator);
          break;
        }        
    }

    public function unaryOperator($operator)
    {
      switch ($operator) {
        case '-':
          switch ($this->type) {
            case self::TYPE_INT:              
              $this->intVal = -$this->intVal;
            break;
            case self::TYPE_DOUBLE:              
              $this->doubleVal = -$this->doubleVal;
            break;
            default:
              throw new \Exception('Post increment is not supported by type ' . $this->type);
            break;
          }
        break;
        case '+':
          switch ($this->type) {
            case self::TYPE_INT:              
              $this->intVal = +$this->intVal;
            break;
            case self::TYPE_DOUBLE:              
              $this->doubleVal = +$this->doubleVal;
            break;
            default:
              throw new \Exception('Post increment is not supported by type ' . $this->type);
            break;
          }
        break;
        default:
            throw new \Exception('Not supported unary operator ' . $operator);
        break;
      }
    }
}