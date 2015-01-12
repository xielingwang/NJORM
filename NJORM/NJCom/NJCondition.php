<?php
/**
 * @Author: byamin
 * @Date:   2014-12-21 16:51:57
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-01-13 01:28:33
 */
namespace NJORM\NJCom;
use \NJORM\NJCom\NJStringifiable;
use \NJORM\NJMisc;
class NJCondition implements NJStringifiable{

  const TYPE_EXPR = 0;
  const TYPE_AND = 1;
  const TYPE_OR = 2;

  protected $_conditions = array();
  protected $_parameters;

  protected $_type;
  protected function __construct() {
  }

  public static function fact($arg) {
    $class = get_called_class();
    $rc = new \ReflectionClass($class);
    $inst = $rc->newInstanceArgs();

    if(!($arg instanceof $class)){
      $arg = $rc->newInstanceArgs();
      $arg->parse(func_get_args());
    }
    $inst->addCondition($arg);

    return $inst;
  }

  protected function call_or() {
    $this->addCondition('or', $arg);
  }

  protected function call_and($arg) {
    $this->addCondition('and', $arg);
  }

  protected function call_close() {
    return self::fact($this);
  }

  public function __call() {
    return $this;
  }

  public function type() {
    $args = func_get_args();
    if(count($args)) {
      $v = array_shift($args);
      if(!in_array($v, array(self::TYPE_EXPR, self::TYPE_AND, self::TYPE_OR))) {
        trigger_error('Unexected condition type: ' . $v, E_USER_ERROR);
      }
      $this->_type = $v;
    }
    return $this->_type;
  }

  public function add($arg) {
    if($arg instanceof NJCondition)
      $this->_conditions[] = $arg;
    elseif(is_array($arg)) {
      $this->_conditions[] = static::fact($arg);
    }
    else {
      trigger_error('argument for NJConditon::add() neither an instance of NJCondition or an array');
    }
    return $this;
  }

  protected function andConditions($arg) {
    $this->type(self::TYPE_AND);
    $this->_conditions = array();
    foreach($arg as $c) {
      $this->add($c);
    }
    return $this;
  }

  protected function orConditions($arg) {
    $this->type(self::TYPE_OR);
    $this->_conditions = array();
    foreach($arg as $c) {
      $this->add($c);
    }
    return $this;
  }

  protected function expr($arg) {
    $this->type(self::TYPE_EXPR);
    if(strpos($arg[0], '%') !== false) {
      $arg[0] = str_replace('%s', "'%s'", $arg[0]);
      $this->_conditions = call_user_func_array('sprintf', $arg);
      return $this;
    }

    do {
      if(count($arg) <= 1) {
        $stmt = preg_replace("", replacement, subject)
        $arg = preg_split('/.+['.NJMisc::supportedOperators('|').'].+/i', $stmt);
      }

      if(count($arg) <= 1) {
        $this->_conditions = array_shift($arg);
        break;
      }
      if(count($arg) <= 2) {
        $v = array_pop($arg);
        $arg[] = '=';
        $arg[] = $v;
      }
      if(count($arg) >= 3) {
        $arg[1] = NJMisc::formatOperator($arg[1]);

        // between
        if(in_array($arg[1], array('BETWEEN', 'NOT BETWEEN'))) {
          if(count($arg) < 4) {
            trigger_error('"between" operetaor expr expects 4 arguments');
          }

          $arg[2] = sprintf("%s AND %s", NJMisc::formatValue($arg[2]), NJMisc::formatValue($arg[3]));
        }

        // IS (NOT) NULL
        elseif(is_null($arg[2]) || is_bool($arg[2])) {
          $arg[1] = NJMisc::operatorForNull($arg[1]);
          if(is_null($arg[2]))
            $arg[2] = 'NULL';
          else
            $arg[2] = $arg[2] ? 'TRUE' : 'FALSE';
        }

        // (NOT) IN (...)
        elseif(is_array($arg[2])) {
          if(empty($arg[2])) {
            $arg[1] = NJMisc::operatorForNull($arg[1]);
            $arg[2] = 'NULL';
          }
          else {
            $arg[1] = NJMisc::operatorForArray($arg[1]);
            $arg[2] = NJMisc::formatValue($arg[2]);
          }
        }

        // A VALUE OR A Field
        else {
          $arg[2] = NJMisc::formatValue($arg[2]);
        }

        $arg[0] = NJMisc::formatFieldName($arg[0]);
        $this->_conditions = sprintf("%s %s %s", $arg[0], $arg[1], $arg[2]);
      }
    }
    while(0);

    return $this;
  }

  protected function andExprStringify($enclose) {
    $strs = array();
    foreach($this->_conditions as $c) {
      $strs[] = $c->stringify($c->type() === self::TYPE_OR);
    }
    $op = ' AND ';
    $string = implode($op, $strs);
    return $enclose ? $string = ('('.$string.')') : $string;
  }

  protected function orExprStringify($enclose) {
    $strs = array();
    foreach($this->_conditions as $c) {
      $strs[] = $c->stringify($c->type() === self::TYPE_OR);
    }
    $op = ' OR ';
    $string = implode($op, $strs);
    return $enclose ? $string = ('('.$string.')') : $string;
  }

  public function stringify($enclose = false) {
    if($this->_type === self::TYPE_EXPR)
      return $this->_conditions;
    elseif($this->_type === self::TYPE_AND)
      return $this->andExprStringify($enclose);
    else 
      return $this->orExprStringify($enclose);
  }

  public function __toString() {
    return "WHERE " . $this->stringify();
  }

  public static function N() {
    $c = new self(func_get_args());
    return $c;
  }

  public static function O() {
    $c = new self();
    return $c->orConditions(func_get_args());
  }
}