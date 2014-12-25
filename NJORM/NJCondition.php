<?php
/**
 * @Author: byamin
 * @Date:   2014-12-21 16:51:57
 * @Last Modified by:   byamin
 * @Last Modified time: 2014-12-25 01:12:08
 */
namespace NJORM;
class NJCondition {
  const TYPE_EXPR = 0;
  const TYPE_AND = 1;
  const TYPE_OR = 2;
  protected $_supported_operators = array('=','>=','>','<=','<','<>','!=','<=>','IS','IS NOT','IN','NOT IN','BETWEEN','NOT BETWEEN','REGEXP', 'NOT REGEXP','LIKE','NOT LIKE');

  protected $_data;
  protected $_type;
  function __construct() {
    $arg = func_get_args();

    if(!count($arg)) {
      $this->type(self::TYPE_AND);
    }

    if(is_scalar($arg[0])) {
      $this->expr($arg);
      return;
    }

    $this->andSth($arg);
  }

  protected function selfFactory($arg) {
    $class = get_class($this);
    $rc = new \ReflectionClass($class);
    return $rc->newInstanceArgs($arg);
  }

  public function type() {
    $args = func_get_args();
    if(count($args)) {
      $v = array_shift($args);
      if(!in_array($v, array(self::TYPE_EXPR, self::TYPE_AND, self::TYPE_OR))) {
        throw new \Exception("Condition Unexpected Type!");
      }
      $this->_type = $v;
    }
    return $this->_type;
  }

  public function add($arg) {
    if($arg instanceof NJCondition)
      $this->_data[] = $arg;
    elseif(is_array($arg)) {
      $this->_data[] = $this->selfFactory($arg);
    }
    else {
      throw new \Exception("Not NJConditon NOT array!");
    }
    return $this;
  }

  protected function andSth($arg) {
    $this->type(self::TYPE_AND);
    $this->_data = array();
    foreach($arg as $c) {
      $this->add($c);
    }
    return $this;
  }

  protected function orSth($arg) {
    $this->type(self::TYPE_OR);
    $this->_data = array();
    foreach($arg as $c) {
      $this->add($c);
    }
    return $this;
  }

  protected function _operator_is($op) {
    if(in_array($op, array('==','='))) {
      $op = 'IS';
    }
    elseif(in_array($op, array('!=', '<>'))) {
      $op = 'IS NOT';
    }
    return $op;
  }

  protected function _operator_in($op) {
    if(in_array($op, array('==','='))) {
      $op = 'IN';
    }
    elseif(in_array($op, array('!=', '<>'))) {
      $op = 'NOT IN';
    }
    return $op;
  }

  protected function _value_standardize($v) {
    if(strpos($v, '`') !== false)
      return $v;
    if(!is_numeric($v)) {
      return '\''.addslashes($v).'\'';
    }
    return $v;
  }

  protected function _operator_standardize($op) {
    $op = preg_replace('/\s+/i', ' ', strtoupper($op));
    if(!in_array($op, $this->_supported_operators)) {
      throw new \Exception( "illegal operator " . $op );
    }
    return $op;
  }

  protected function _field_standardize($f) {
    return '`' . $f . '`';
  }

  protected function _implode_array(array $arr) {
    foreach($arr as &$v) {
      $v = $this->_value_standardize($v);
    }
    return '(' . implode(',', $arr) . ')';
  }

  protected function expr($arg) {
    $this->type(self::TYPE_EXPR);
    if(strpos($arg[0], '%') !== false) {
      $arg[0] = str_replace('%s', "'%s'", $arg[0]);
      $this->_data = call_user_func_array('sprintf', $arg);
      return $this;
    }

    do {
      if(count($arg) <= 1) {
        $this->_data = array_shift($arg);
        break;
      }
      if(count($arg) <= 2) {
        $v = array_pop($arg);
        $arg[] = '=';
        $arg[] = $v;
      }
      if(count($arg) >= 3) {
        $arg[1] = $this->_operator_standardize($arg[1]);

        // between
        if(in_array($arg[1], array('BETWEEN', 'NOT BETWEEN'))) {
          if(count($arg) < 4)
            throw new \Exception('Forth argument is need for `between` operator');
          $arg[2] = sprintf("%s AND %s", $this->_value_standardize($arg[2]), $this->_value_standardize($arg[3]));
        }

        // IS (NOT) NULL
        elseif(is_null($arg[2]) || is_bool($arg[2])) {
          $arg[1] = $this->_operator_is($arg[1]);
          if(is_null($arg[2]))
            $arg[2] = 'NULL';
          else
            $arg[2] = $arg[2] ? 'TRUE' : 'FALSE';
        }

        // (NOT) IN (...)
        elseif(is_array($arg[2])) {
          if(empty($arg[2])) {
            $arg[1] = $this->_operator_is($arg[1]);
            $arg[2] = 'NULL';
          }
          else {
            $arg[1] = $this->_operator_in($arg[1]);
            $arg[2] = $this->_implode_array($arg[2]);
          }
        }

        // A VALUE OR A Field
        else {
          $arg[2] = $this->_value_standardize($arg[2]);
        }

        $arg[0] = $this->_field_standardize($arg[0]);
        $this->_data = sprintf("%s %s %s", $arg[0], $arg[1], $arg[2]);
      }
    }
    while(0);

    return $this;
  }

  protected function expr2Str($enclose) {
    return $this->_data;
  }

  protected function and2Str($enclose) {
    $strs = array();
    foreach($this->_data as $c) {
      $strs[] = $c->toString($c->type() === self::TYPE_OR);
    }
    $op = ' AND ';
    $string = implode($op, $strs);
    return $enclose ? $string = ('('.$string.')') : $string;
  }

  protected function or2Str($enclose) {
    $strs = array();
    foreach($this->_data as $c) {
      $strs[] = $c->toString($c->type() === self::TYPE_OR);
    }
    $op = ' OR ';
    $string = implode($op, $strs);
    return $enclose ? $string = ('('.$string.')') : $string;
  }

  public function toString($enclose = false) {
    if($this->_type === self::TYPE_EXPR)
      return $this->expr2Str($enclose);
    elseif($this->_type === self::TYPE_AND)
      return $this->and2Str($enclose);
    else 
      return $this->or2Str($enclose);
  }

  public function toWhere() {
    return "WHERE " . $this->toString();
  }

  public static function N() {
    $c = new self(func_get_args());
    return $c;
  }

  public static function O() {
    $c = new self();
    return $c->orSth(func_get_args());
  }
}