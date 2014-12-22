<?php
/**
 * @Author: byamin
 * @Date:   2014-12-21 16:51:57
 * @Last Modified by:   byamin
 * @Last Modified time: 2014-12-23 01:22:54
 */
namespace NJORM;
class NJCondition {
  const TYPE_EXPR = 0;
  const TYPE_AND = 1;
  const TYPE_OR = 2;

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
        throw new Exception("Condition Unexpected Type!");
      }
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
      throw new Exception("Not NJConditon NOT array!");
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
      if(count($arg) <= 3) {
        if(!is_numeric($arg[2]))
          $arg[2] = sprintf("'%s'", $arg[2]);
        $this->_data = sprintf("`%s` %s %s", $arg[0], $arg[1], $arg[2]);
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
      $strs[] = $w->toString($c->type() === self::TYPE_OR);
    }
    $op = ' AND ';
    $string = implode($op, $strs);
    return $enclose ? $where = ('('.$where.')') : $where;
  }

  protected function or2Str($enclose) {
    $strs = array();
    foreach($this->_data as $c) {
      $strs[] = $w->toString($c->type() === self::TYPE_OR);
    }
    $op = ' OR ';
    $string = implode($op, $strs);
    return $enclose ? $where = ('('.$where.')') : $where;
  }

  public function toString($enclose = false) {
    if($this->_type === self::TYPE_EXPR)
      return $this->expr2Str($enclose);
    elseif($this->_type === self::TYPE_AND)
      return $this->and2Str($enclose);
    else 
      return $this->or2Str($enclose);
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