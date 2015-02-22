<?php
/**
 * @Author: byamin
 * @Date:   2014-12-21 16:51:57
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-02-22 19:53:39
 */
namespace NJORM\NJSql;
use NJORM\NJMisc;
use NJORM\NJSql\NJTable;
use NJORM\NJValid;
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
   * fact(array("a" => 2, "b" => 3, "c" => "d")) => `a` = 2 AND `b` = 3 `c` = 'd'
   * @param  [type] $arg [description]
   * @see  factX
   * @return [type]      [description]
   */
  public static function fact($arg) {
    $class = __CLASS__;
    $inst = new $class;

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
    foreach(func_get_args() as $arg) {
      if(is_array($arg))
        throw new \Exception("error arguments for addCondition");
    }
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

  protected function _resolveSubConditions() {
    if(!is_string($this->_conditions)) {
      return $this;
    }

    // 1.Preprocession
    $sqlcnd = $this->_conditions;
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
    $this->_conditions = null;

    $arrLinkers = array_filter(explode(' ', str_replace($matched, '', $sqlcnd)));
    ksort($arrConds);
    $_parameters = $this->_parameters;

    $argsForAddCond = array();
    foreach ($arrConds as $cond) {
      $params = array();
      $njcond = $this->_putBackStringsAndParameters($cond, $strings, $_parameters);
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

    $ret = static::fact($cnd)->_setParameters($ps);
    return $ret;
  }

  // supported %s, %d, %f, %l
  protected function _parseWithParameters(&$args) {
    // 1.transfer % to @#PCNT#@ and ? to @#QUSTN#@
    $format = preg_replace_callback("/'[^']*[%?][^']*'/", function($matches){
      return str_replace(array('%','?'), array('@#PCNT#@','@#QUSTN#@'), $matches[0]);
    }, array_shift($args));

    // 2.capture printf arguments and their offset
    $format = str_replace('%s', "'%s'", $format);
    $r = preg_match_all("/%[sdfl]/", $format, $matches, PREG_PATTERN_ORDER|PREG_OFFSET_CAPTURE);
    $offsetPtf = array();
    foreach($matches[0] as $_){
      $offsetPtf[] = $_[1];
    }

    // 3.catpure question marks and their offset
    $offsetQM = array();
    $r = 0;
    while(($r = strpos($format, '?', $r)) !== false) {
      $offsetQM[] = $r++;
    }

    // 4. process the sprintf arguments and bindPara parameters
    $offsetMarks = array_merge($offsetPtf, $offsetQM);
    if(count($args) < count($offsetMarks)) {
      trigger_error('Too few arguments for NJCondition::parse()');
    }
    sort($offsetMarks);
    $offsetMarks = array_flip($offsetMarks);
    $args4sprintf = array();
    foreach($offsetPtf as $idx) {
      $args4sprintf[] = $args[$offsetMarks[$idx]];
    }

    // 5.get parameters and condition statement
    $this->_parameters = array_diff($args, $args4sprintf);
    array_unshift($args4sprintf, $format);
    $this->_conditions = str_replace(array('@#PCNT#@','@#QUSTN#@'), array('%','?'), call_user_func_array('sprintf', $args4sprintf));

    // 6.resolve sub condition statements
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
        $this->_conditions = array_shift($args);
        $this->_resolveSubConditions();
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
          // echo "unexpected type\n";
          // var_export($cond);
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
  protected function _setParameters($ps) {
    $this->_parameters = $ps;
    return $this;
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