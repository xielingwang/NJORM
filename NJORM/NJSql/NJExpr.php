<?php
/**
 * @Author: byamin
 * @Date:   2015-02-17 19:56:26
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-06 00:25:05
 */
namespace NJORM\NJSql;
class NJExpr{
  protected $_parameters = array();
  protected $_value = null;
  protected $_alias = null;

  public function __construct() {
    if(func_num_args() > 0) {
      $this->parse(func_get_args());
    }
  }

  public static function fact() {
    $ClassNJEpr = __CLASS__;
    $inst = new $ClassNJEpr();

    if(func_num_args() > 0) {
      $inst->parse(func_get_args());
    }

    return $inst;
  }

  function __call($name, $args) {
    if(strtolower($name) == 'as') {
      return call_user_func_array(array($this, '_as'), $args);
    }
  }

  public function _as() {
    if(func_num_args() > 0) {
      $this->_alias = func_get_arg(0);
      return $this;
    }
    else
      return $this->_alias;
  }

  public function parse($args) {
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
      trigger_error('Too few arguments for NJExpr::parse()');
    }
    sort($offsetMarks);
    $offsetMarks = array_flip($offsetMarks);
    $args4sprintf = array();
    foreach($offsetPtf as $idx) {
      $args4sprintf[] = $args[$offsetMarks[$idx]];
    }

    // 5.get parameters and condition statement
    $this->_SetParameters(array_diff($args, $args4sprintf));
    array_unshift($args4sprintf, $format);
    $this->_SetValue(str_replace(array('@#PCNT#@','@#QUSTN#@'), array('%','?'), call_user_func_array('sprintf', $args4sprintf)));

    return $this;
  }

  public function addParameters() {
    if(empty($this->_parameters)){
      $this->_parameters = array();
    }
    elseif(!is_array($this->_parameters)) {
      $this->_parameters = array($this->_parameters);
    }
    foreach(func_get_args() as $arg) {
      if(is_array($arg)) {
        foreach($arg as $sarg) {
          $this->_parameters[] = $sarg;
        }
      }
      else {
        $this->_parameters[] = $arg;
      }
    }
    return $this;
  }

  public function parameters() {
    $ClassNJEpr = __CLASS__;

    if(is_array($this->_value)) {
      $params = array();
      foreach($this->_value as $cond) {
        if($cond instanceof $ClassNJEpr) {
          $params = array_merge($params, $cond->parameters());
        }
      }
      return $params;
    }
    if($this->_value instanceof $ClassNJEpr) {
      return $this->_value->parameters();
    }
    if(empty($this->_value)
      or is_string($this->_value)
      or is_numeric($this->_value)) {
      return $this->_parameters;
    }
    trigger_error('unexpected type for condtion:' . gettype($this->_value));
    return $this->_parameters;
  }

  protected function _SetParameters() {
    $this->_parameters = array();
    if(func_num_args() > 0) {
      return call_user_func_array(array($this, 'addParameters'), func_get_args());
    }
    return $this;
  }

  protected function _SetValue($value) {
    $this->_value = $value;
    return $this;
  }

  protected function _GetValue() {
    return $this->_value;
  }

  public function isEmpty() {
    return empty($this->_value);
  }

  public function __toString() {
    return $this->stringify();
  }

  public function stringify() {
    $ClassNJEpr = __CLASS__;

    if(is_string($this->_value) or is_numeric($this->_value)) {
      return (string)$this->_value;
    }
    if($this->_value instanceof $ClassNJEpr) {
      return $this->stringify();
    }
    if(is_array($this->_value)){
      $strs = array();
      foreach($this->_value as $iter) {
        if(is_string($iter)) {
          $str = strtoupper($iter);
        }
        elseif($iter instanceof $ClassNJEpr) {
          $str = $iter->stringify();
          if(is_callable(array($iter, 'isEnclosed'))) {
            if($iter->isEnclosed()) {
              $str = '('.$str.')';
            }
          }
        }
        else{
          trigger_error('unexpected type for condition.' . gettype($cond));
        }
        $strs[] = $str;
      }
      return implode(' ', $strs);
    }
  }
}
