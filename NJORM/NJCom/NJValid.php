<?php
/**
 * @Author: byamin
 * @Date:   2015-01-07 00:27:39
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-01-08 01:42:28
 */
namespace NJORM\NJCom;

class NJValid {
  protected static function instance() {
    static $inst;
    if(!$inst) {
      $inst = new NJValid();
    }
    return $inst;
  }

  public static function register($rule, $callable) {
    self::instance()->addRule($rule, $callable);
  }

  public static function R() {
    return call_user_func_array(array(self::instance(), 'rule'), func_get_args());
  }

  public function rule($rule) {
    $args = func_get_args();
    array_shift($args);
    $that =& $this;
    return function ($val) use ($that, $rule, $args) {
      array_unshift($args, $rule, $val);
      return call_user_func_array(array($that, 'checkRule'), $args);
    };
  }

  protected $rules = array();
  public function addRule($rule, $callable) {
    if(!is_callable($callable)) {
      trigger_error('Argument 2 expects a callable value for NJValid::addRule()');
    }
    $this->rules[$rule] = $callable;
  }

  public function checkRule($rule) {
    $args = func_get_args();
    array_shift($args);
    if(!array_key_exists($rule, $this->rules)) {
      trigger_error("Rule {$rule} not found!");
    }

    return call_user_func_array($this->rules[$rule], $args);
  }

  public function __construct() {
    $this->addRule('notEmpty', function($val){
      return !empty($val);
    });
    $this->addRule('in', 'in_array');
    $this->addRule('notIn', function($val, $arr){
      if(!is_array($arr)) {
        trigger_error('Argument 2 expects an array for rule "notIn"');
      }
      return !in_array($val, $arr);
    });
    $this->addRule('array', 'is_array');
    $this->addRule('integer', 'is_int');
    $this->addRule('accepted', function($val){
      $this->checkRule('in', strtolower(trim($val)), array('yes','1','on','true'));
    });
    $this->addRule('numeric', 'is_numeric');
    $this->addRule('max', function($val, $max){
      return $val <= $max;
    });
    $this->addRule('min', function($val, $min){
      return $val >= $min;
    });
    $this->addRule('regex', function($val, $pattern){
      return preg_match($pattern, $val);
    });
    $this->addRule('email', function($val){
      return $this->checkRule('regex', $val, '/^[a-z0-9_.]+@(?:[a-z0-9_-]+\.)+[a-z0-9_]+$/i');
    });
    $this->addRule('url', function($val){
      return $this->checkRule('regex', $val, "/^https?:\/\/(?:[a-z0-9_-]+\.)+[a-z0-9_]+$/i");
    });
    $this->addRule('ip', function($val){
      return $this->checkRule('regex', $val, '/^(?:\d{1,3}){3}\d{1,3}$/');
    });

    // string
    $this->addRule('alpha', function($val){
      $this->checkRule('regex', $val, '/^[a-z]$/i');
    });
    $this->addRule('alphaNum', function($val){
      $this->checkRule('regex', $val, '/^[a-z0-9]$/i');
    });
    $this->addRule('length', function($val, $len){
      return strlen($val) == $len;
    });
    $this->addRule('lengthBetween', function($val, $min, $max){
      return $this->checkRule('lengthMin', $val, $min) && $this->checkRule('lengthMax', $val, $max);
    });
    $this->addRule('lengthMin', function($val, $min){
      return strlen($val) >= $min;
    });
    $this->addRule('lengthMax', function($val, $max){
      return strlen($val) <= $max;
    });

    $this->addRule('contains', function($val, $need){
      return strpos($val, $need) != false;
    });
  }
}