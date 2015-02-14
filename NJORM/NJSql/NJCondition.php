<?php
/**
 * @Author: byamin
 * @Date:   2014-12-21 16:51:57
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-02-15 00:01:40
 */
namespace NJORM\NJSql;
use NJORM\NJMisc;
use NJORM\NJSql\NJTable;
use NJORM\NJInterface;
class NJCondition implements NJInterface\NJStringifiable{

  protected static $_table;
  static function setTable($table) {
    if(is_string($table))
      $table = NJTable::$table();
    static::$_table = $table;
  }

  protected $_conditions;
  protected $_parameters = array();

  /**
   * [fact description]
   * fact(true) => true
   * fact("field", 3) => `field` = 3
   * fact("`field`", 3") => `field` = 3
   * fact("field", ">", 3) => `field` > 3
   * fact("field > ?", 3) => `field` > ? -- 3
   * @param  [type] $arg [description]
   * @return [type]      [description]
   */
  public static function fact($arg) {
    $class = get_called_class();
    $rc = new \ReflectionClass($class);
    $inst = $rc->newInstanceArgs();

    if(!($arg instanceof $class)) {
      if(is_array($arg)) {
        $inst->parse($arg);
      }
      else{
        $inst->parse(func_get_args());
      }
    }
    else{
      $inst->addCondition($arg);
    }

    return $inst;
  }

  public static function factX() {
    $class = get_called_class();
    $rc = new \ReflectionClass($class);
    $inst = $rc->newInstanceArgs();

    $op = null;
    foreach(func_get_args() as $v) {
      $is_val = true;
      if(is_array($v)) {
        $v = $rc->newInstanceArgs()->parse($v);
      }
      elseif(!($v instanceof $class)) {
        if(!in_array(strtolower($v), array('or','and'))) {
          $v = $rc->newInstanceArgs()->parse($v);
        }
        else {
          $op = strtolower($v);
          $is_val = false;
        }
      }

      if(!$is_val) {
        continue;
      }

      if(!$op && !$inst->isEmpty()) {
        $op = 'and';
      }

      if($op)
        $inst->addCondition($op, $v);
      else
        $inst->addCondition($v);

      $op = null;
    }

    return $inst;
  }

  protected function call_or($arg) {
    if(func_num_args() < 1) {
      trigger_error('NJCondition "or" methods expects least 1 parameter.');
    }
    $class = get_class($this);
    if($arg instanceof $class)
      $this->addCondition('or', $arg);
    else
      $this->addCondition('or', call_user_func_array($class.'::fact', func_get_args()));
    return $this;
  }

  public function isEmpty() {
    return empty($this->_conditions);
  }

  protected function call_and($arg) {
    if(func_num_args() < 1) {
      trigger_error('NJCondition "and" methods expects least 1 parameter.');
    }
    $class = get_class($this);
    if($arg instanceof $class)
      $this->addCondition('and', $arg);
    else 
      $this->addCondition('and', call_user_func_array($class.'::fact', func_get_args()));
    return $this;
  }

  protected function call_close() {
    $obj = clone $this;
    $this->_conditions = array($obj);
    return $this;
  }

  public function __call($name, $args) {
    if(in_array($name, array('or', 'and', 'close'))) {
      return call_user_func_array(array($this, 'call_'.$name), $args);
    }
    trigger_error('NJCondition does not undefined method: ' . $name);
  }

  protected function addCondition() {
    if(is_null($this->_conditions)) {
      $this->_conditions = array();
    }
    elseif(!is_array($this->_conditions)){
      $this->call_close();
    }

    if(!count($this->_conditions)) {
      $this->_conditions = func_get_args();
    }
    else {
      $this->_conditions = array_merge($this->_conditions, func_get_args());
    }

    return $this;
  }

  protected function _parseWithParameters(&$args) {
    $format = array_shift($args);
    $format = preg_replace_callback("/'[^']*[%?][^']*'/", function($matches){
      $content = str_replace('%', '@#PCNT#@', $matches[0]);
      return str_replace('?', '@#QUSTN#@', $content);
    }, $format);

    $format = str_replace('%s', "'%s'", $format);
    $r = preg_match_all("/%[sdf]/", $format, $matches, PREG_PATTERN_ORDER|PREG_OFFSET_CAPTURE);
    $pf_idx = array();
    foreach($matches[0] as $_){
      $pf_idx[] = $_[1];
    }

    $bp_idx = array();
    $r = 0;
    while(($r = strpos($format, '?', $r)) !== false) {
      $bp_idx[] = $r++;
    }
    $sorted = array_merge($pf_idx, $bp_idx);
    if(count($args) < count($sorted)) {
      trigger_error('Too few arguments for NJCondition::parse()');
    }
    
    sort($sorted);
    $sorted = array_flip($sorted);
    $pf_args = array();
    foreach($pf_idx as $idx) {
      $pf_args[] = $args[$sorted[$idx]];
    }
    $this->_parameters = array_diff($args, $pf_args);
    array_unshift($pf_args, $format);

    $string = call_user_func_array('sprintf', $pf_args);
    $string = str_replace('@#PCNT#@', '%', $string);
    $string = str_replace('@#QUSTN#@', '?', $string);
    $this->_conditions = $string;

    return $this;
  }

  public function parse($args) {
    if(!is_array($args)) {
      var_dump($args);
      trigger_error('args must be an array!');
    }

    if( preg_match('/%[sdf]|\?/', $args[0]) ) {
      return $this->_parseWithParameters($args);
    }

    do {

      if(count($args) <= 1) {
        $this->_conditions = array_shift($args);
        break;
      }
      if(count($args) <= 2) {
        $v = array_pop($args);
        $args[] = '=';
        $args[] = $v;
      }
      if(count($args) >= 3) {
        $args[1] = NJMisc::formatOperator($args[1]);

        // between
        if(in_array($args[1], array('BETWEEN', 'NOT BETWEEN'))) {
          if(count($args) < 4) {
            trigger_error('"between" operetaor expr expects 4 arguments');
          }

          $args[2] = sprintf("%s AND %s", NJMisc::formatValue($args[2]), NJMisc::formatValue($args[3]));
        }

        // IS (NOT) NULL
        elseif(is_null($args[2]) || is_bool($args[2])) {
          $args[1] = NJMisc::operatorForNull($args[1]);
          if(is_null($args[2]))
            $args[2] = 'NULL';
          else
            $args[2] = $args[2] ? 'TRUE' : 'FALSE';
        }

        // (NOT) IN (...)
        elseif(is_array($args[2])) {
          if(empty($args[2])) {
            $args[1] = NJMisc::operatorForNull($args[1]);
            $args[2] = 'NULL';
          }
          else {
            $args[1] = NJMisc::operatorForArray($args[1]);
            $args[2] = NJMisc::formatValue($args[2]);
          }
        }

        // A VALUE OR A Field
        else {
          $args[2] = NJMisc::formatValue($args[2]);
        }

        if(static::$_table) {
          $args[0] = static::$_table->getField($args[0]);
        }
        $args[0] = NJMisc::formatFieldName($args[0]);
        $this->_conditions = sprintf("%s %s %s", $args[0], $args[1], $args[2]);
      }
    }
    while(0);

    return $this;
  }

  public function stringify() {
    if(is_array($this->_conditions)) {
      $class = get_class($this);
      $strs = array();
      foreach($this->_conditions as $cond) {
        if(is_string($cond)) {
          $strs[] = strtoupper($cond);
        }
        elseif($cond instanceof $class) {
          $str = $cond->stringify();
          if($cond->enclosed())
            $str = '('.$str.')';
          $strs[] = $str;
        }
        else {
          trigger_error('unexpected type for condition.' . gettype($cond));
        }
      }
      return implode(' ', $strs);
      if(count($strs) > 1)
        $str_c = sprintf('(%s)', $str_c);
      return $str_c;
    }
    if(is_string($this->_conditions) or is_numeric($this->_conditions)) {
      return $this->_conditions;
    }
    trigger_error('unexpected type for condtion:' . gettype($this->_conditions));
  }
  public function parameters() {
    if(is_array($this->_conditions)) {
      $class = get_class($this);
      $params = array();
      foreach($this->_conditions as $cond) {
        if($cond instanceof $class) {
          $params = array_merge($params, $cond->parameters());
        }
      }
      return $params;
    }
    if(is_string($this->_conditions) or is_numeric($this->_conditions)) {
      return $this->_parameters;
    }
    trigger_error('unexpected type for condtion:' . gettype($this->_conditions));
  }

  public function enclosed() {
    return is_array($this->_conditions) && count($this->_conditions) > 1 && in_array('or', $this->_conditions);
  }

  public function __toString() {
    return "WHERE " . $this->stringify();
  }
}