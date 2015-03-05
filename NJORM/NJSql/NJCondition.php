<?php
/**
 * @Author: byamin
 * @Date:   2014-12-21 16:51:57
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-05 16:42:07
 */
namespace NJORM\NJSql;
use NJORM\NJMisc;
use NJORM\NJValid;
class NJCondition extends NJExpr{

  protected static $s_table;
  static function setTable($table) {
    if(is_string($table))
      $table = NJTable::$table();
    static::$s_table = $table;
  }

  /**
   * [fact description]
   * fact(true) => true
   * fact("field", 3) => `field` = 3
   * fact("`field`", 3") => `field` = 3
   * fact("field", ">", 3) => `field` > 3
   * fact("field > ?", 3) => `field` > ? -- 3
   * fact(array("a" => 2, "b" => 3, "c" => "d")) => `a` = 2 AND `b` = 3 `c` = 'd'
   * @param  [type] $arg [description]
   * @see  factX
   * @return [type]      [description]
   */
  public static function fact() {
    $class = __CLASS__;
    $inst = new $class;

    if(func_num_args() <= 0)
      return $inst;

    $arg = func_get_arg(0);

    // case like: fact(array, njcond, array)
    if(($arg instanceof $class || is_array($arg)) and func_num_args() > 1){
      return call_user_func_array(__CLASS__.'::factX', func_get_args());
    }

    if(!($arg instanceof $class)) {
      if(is_array($arg)) {
        // case like: fact(array('c' => 1, 'd' => 2))
        if(!is_int(current(array_keys($arg)))) {
          $arr = array();
          foreach ($arg as $key => $val) {
            $arr[] = $class::fact($key, $val);
          }
          $inst = call_user_func_array(__CLASS__.'::factX', $arr);
        }
        // case like: fact(array('c', '>', '2'))
        else {
          $inst->parse($arg);
        }
      }
      // case like: fact('c', '>', 3)
      else{
        $inst->parse(func_get_args());
      }
    }
    // case like: fact($njcond)
    else{
      $inst->addCondition($arg);
    }

    return $inst;
  }

  protected static function factX() {
    $class = __CLASS__;
    $inst = new $class;

    $op = null;
    foreach(func_get_args() as $v) {
      $is_val = true;
      if(is_array($v)) {
        $v = $class::fact($v);
      }
      elseif(!($v instanceof $class)) {
        if(!in_array(strtolower($v), array('or','and'))) {
          $v = $class::fact($v);
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
    $this->_SetValue(array($obj));
    $this->_SetParameters();
    return $this;
  }

  public function __call($name, $args) {
    if(in_array($name, array('or', 'and', 'close'))) {
      return call_user_func_array(array($this, 'call_'.$name), $args);
    }
    trigger_error('NJCondition does not undefined method: ' . $name);
  }

  protected function addCondition() {
    foreach(func_get_args() as $arg) {
      if(is_array($arg))
        throw new \Exception("error arguments for addCondition");
    }

    $val = $this->_GetValue();

    if(empty($val)) {
      $val = array();
    }
    elseif(!is_array($val)){
      $this->call_close();
      $val = $this->_GetValue();
    }

    if(!count($val)) {
      $val = func_get_args();
    }
    else {
      $val = array_merge($val, func_get_args());
    }

    return $this->_SetValue($val);
  }

  protected function _resolveSubConditions() {
    $sqlcnd = $this->_GetValue();
    if(!is_string($sqlcnd)) {
      return $this;
    }

    // 1.Preprocession
    $sqlcnd = str_replace(array("\'","''"), "@##QUTESC##@", $sqlcnd);
    if(preg_match_all("/'([^']+)'/i", $sqlcnd, $matches, PREG_SET_ORDER)) {
      foreach($matches as $m) {
        $sqlcnd = str_replace($m[0],'@##STRNG##@',$sqlcnd);
        $strings[] = str_replace("@##QUTESC##@", "'", $m[1]);
      }
    }

    // 2.Explode Conditions
    $matched = array();
    $arrConds = array();
    $arrLinkers = array();

    // 2.1.Deal with Normal Operators
    $regexOp = str_replace(' ', '\s+', NJMisc::normalOperators('|'));
    $regexOp = sprintf('/(`?\w+`?)\s*(%s)\s*(\S+)/i', $regexOp);
    if(preg_match_all($regexOp, $sqlcnd, $matches,PREG_SET_ORDER|PREG_OFFSET_CAPTURE)) {
      foreach($matches as $m) {
        $matched[] = $m[0][0];
        $arrConds[$m[0][1]] = array($m[1][0],$m[2][0],$m[3][0]);
      }
    }

    // 2.2.DEAL WITH (NOT) BETWEEN
    $regexOp = '/(`?\w+`?)\s((?:not\s+)?BETWEEN)\s+(\S+)\s+AND\s+(\S+)/i';
    if(preg_match_all($regexOp, $sqlcnd, $matches,PREG_SET_ORDER|PREG_OFFSET_CAPTURE)) {
      foreach($matches as $m) {
        $matched[] = $m[0][0];
        $arrConds[$m[0][1]] = array($m[1][0],$m[2][0],$m[3][0],$m[4][0]);
      }
    }

    // 2.2.DEAL WITH (NOT) IN
    $regexOp = '/(`?\w+`?)\s((?:not\s+)?IN)\s+\(([^\)]+)\)/i';
    if(preg_match_all($regexOp, $sqlcnd, $matches,PREG_SET_ORDER|PREG_OFFSET_CAPTURE)) {
      foreach($matches as $m) {
        $matched[] = $m[0][0];
        $m3 = array_map(function($v){
          return trim($v);
        }, explode(',',$m[3][0]));
        $arrConds[$m[0][1]] = array($m[1][0],$m[2][0],$m3);
      }
    }

    // 3. Strings and Pramaters
    // 3.1. No need to change
    if(empty($matched)) {
      return $this;
    }

    // 3.2 place back the strings and allocate the parameters
    $_params = $this->parameters();
    $this->_SetValue(null);

    $arrLinkers = array_filter(explode(' ', str_replace($matched, '', $sqlcnd)));
    ksort($arrConds);

    $argsForAddCond = array();
    foreach ($arrConds as $cond) {
      $params = array();
      $njcond = $this->_putBackStringsAndParameters($cond, $strings, $_params);
      if(empty($argsForAddCond) || empty($arrConds))
        $argsForAddCond[] = array($njcond);
      else
        $argsForAddCond[] = array(array_shift($arrLinkers), $njcond);
    }
    foreach($argsForAddCond as $args) {
      call_user_func_array(array($this, 'addCondition'), $args);
    }
    return $this;
  }

  protected function _putBackStringsAndParameters($cond, &$strngs, &$parameters, $returnObject=true) {
    $isfloat = NJValid::V('float');

    $ps = array();
    $cnd = array();
    foreach($cond as $var) {
      if(is_array($var)) {
        list($var, $_ps) = $this->_putBackStringsAndParameters($var, $strngs, $parameters, false);
        $ps = array_merge($ps, $_ps);
      }
      elseif($var == '@##STRNG##@') {
        $var = array_shift($strngs);
      }
      elseif($isfloat($var)) {
        $var = floatval($var);
      }
      elseif(strtolower($var) == 'null') {
        $var = null;
      }
      elseif($var == '?') {
        $ps[] = array_shift($parameters);
      }
      $cnd[] = $var;
    }
    if(!$returnObject)
      return array($cnd, $ps);

    $ret = static::fact($cnd)->_SetParameters($ps);
    return $ret;
  }

  // supported %s, %d, %f, %l
  protected function _parseWithParameters(&$args) {
    parent::parse($args);

    $this->_resolveSubConditions();

    return $this;
  }

  public function parse($args) {
    if(!is_array($args)) {
      trigger_error('args for NJCondition::parse() expects an array!');
    }

    if( preg_match('/%[sdf]|\?/', $args[0]) ) {
      return $this->_parseWithParameters($args);
    }

    do {

      if(count($args) <= 1) {
        $this->_SetValue(array_shift($args));
        $this->_resolveSubConditions();
        break;
      }
      if(count($args) <= 2) {
        $v = array_pop($args);
        $args[] = '=';
        $args[] = $v;
      }
      if(count($args) >= 3) {
        $op = NJMisc::formatOperator($args[1], $args[2]);

        // between
        if(in_array($op, array('BETWEEN', 'NOT BETWEEN'))) {
          if(count($args) < 4) {
            trigger_error('"between" operetaor expr expects 4 arguments');
          }

          $rOp = sprintf("%s AND %s", NJMisc::formatValue($args[2], $this), NJMisc::formatValue($args[3], $this));
        }

        // array, null, bool
        // A VALUE A Field
        else {
          $rOp = NJMisc::formatValue($args[2], $this);
        }

        // fields
        if(static::$s_table) {
          $args[0] = static::$s_table->getField($args[0]);
        }
        $lOp = NJMisc::formatFieldName($args[0]);

        $this->_SetValue(sprintf('%s %s %s', $lOp, $op, $rOp));
      }
    }
    while(0);

    return $this;
  }

  protected function isEnclosed() {
    $v = $this->_GetValue();
    return is_array($v)
      && count($v) > 1
      && in_array('or', $v);
  }

  public function __toString() {
    return "WHERE " . parent::__toString();
  }
}