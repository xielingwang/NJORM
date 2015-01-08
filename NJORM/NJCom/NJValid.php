<?php
/**
 * @Author: byamin
 * @Date:   2015-01-07 00:27:39
 * @Last Modified by:   Amin by
 * @Last Modified time: 2015-01-08 16:33:01
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

  public static function V() {
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
    $this->addRule('in', function($val, $arr, $caseinsensitive=false){
      if(!is_array($arr)) {
        trigger_error('Argument 2 expects an array for rule "in/notIn"');
      }
      $regex = "/^{$val}$/";
      if($caseinsensitive)
        $regex .= 'i';
      return !!preg_grep($regex, $arr);
    });
    $this->addRule('notIn', function($val, $arr, $caseinsensitive=false){
      return !$this->checkRule('in', $val, $arr, $caseinsensitive);
    });
    $this->addRule('array', 'is_array');
    $this->addRule('integer', 'is_int');
    $this->addRule('true', function($val){
      return $this->checkRule('in', trim($val), array('yes','1','on','true'), true);
    });
    $this->addRule('numeric', 'is_numeric');
    $this->addRule('max', function($val, $max){
      return $val <= $max;
    });
    $this->addRule('min', function($val, $min){
      return $val >= $min;
    });
    $this->addRule('between', function($val, $min, $max){
      return $this->checkRule('min', $val, $min)
        && $this->checkRule('max', $val, $max);
    });

    // string
    $this->addRule('alpha', function($val){
      $this->checkRule('regex', $val, '/^[a-z]$/i');
    });
    $this->addRule('word', function($val){
      $this->checkRule('regex', $val, '/^[a-z-]$/i');
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

    $this->addRule('contains', function($val, $need, $caseinsensitive = false){
      return ($caseinsensitive ? stripos($val, $need) : strpos($val, $need)) !== false;
    });

    // regex
    $this->addRule('regex', function($val, $pattern){
      $ret = preg_match($pattern, $val);
      if($ret === false){
        trigger_error('A regex error occurs: "' . $pattern . '"');
      }
      return !!$ret;
    });
    $this->addRule('email', function($val){
      static $email_regex = '/^[\w.+-]+@(?:[\w-]+\.)+\w{2,4}$/i';
      return $this->checkRule('regex', $val, $email_regex);
    });
    $this->addRule('url', function($val){
      static $url_regex = '/^https?:\/\/(?:[\w-]+\.)+\w{2,4}$/i';
      return $this->checkRule('regex', $val, $url_regex);
    });
    $this->addRule('ip', function($val){
      static $ip_regex = '/^(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])$/';
      return $this->checkRule('regex', $val, $ip_regex);
    });
  }
}