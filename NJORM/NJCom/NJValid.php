<?php
/**
 * @Author: byamin
 * @Date:   2015-01-07 00:27:39
 * @Last Modified by:   Amin by
 * @Last Modified time: 2015-01-08 20:18:45
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
    static::instance()->addRule($rule, $callable);
  }

  public static function V() {
    return call_user_func_array(array(static::instance(), 'rule'), func_get_args());
  }

  public function rule($rule) {
    $args = func_get_args();
    array_shift($args);
    return function ($val) use ($rule, $args) {
      array_unshift($args, $rule, $val);
      return call_user_func_array(__CLASS__.'::checkRule', $args);
    };
  }

  protected $rules = array();
  public function addRule($rule, $callable) {
    if(!is_callable($callable)) {
      trigger_error('Argument 2 expects a callable value for NJValid::addRule()');
    }
    $this->rules[$rule] = $callable;
  }

  public function _checkRule($rule) {
    $args = func_get_args();
    array_shift($args);
    if(!array_key_exists($rule, $this->rules)) {
      trigger_error("Rule {$rule} not found!");
    }

    return call_user_func_array($this->rules[$rule], $args);
  }

  public static function checkRule() {
    return call_user_func_array(array(static::instance(), '_checkRule'), func_get_args());
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
      return !static::checkRule('in', $val, $arr, $caseinsensitive);
    });
    $this->addRule('array', 'is_array');
    $this->addRule('integer', 'is_int');
    $this->addRule('float', function($val){
      return !!filter_var($val, FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_THOUSAND);
    });
    $this->addRule('true', function($val, $strict = false){
      $options = null;
      $strict && $options = FILTER_NULL_ON_FAILURE;
      $ret = filter_var($val, FILTER_VALIDATE_BOOLEAN, $options);
      if($ret === NULL) {
        trigger_error(sprintf('"%s" is an invalid boolean value.', $val));
      }
      return $ret;
    });
    $this->addRule('numeric', 'is_numeric');
    $this->addRule('positive', function($val){
      if(!is_numeric($val)) {
        trigger_error(sprintf('"%s" is not a numeric.', $val));
      }
      return $val > 0;
    });
    $this->addRule('negative', function($val){
      return !self::checkRule('positive', $val) && $val;
    });

    $this->addRule('max', function($val, $max){
      return $val <= $max;
    });
    $this->addRule('min', function($val, $min){
      return $val >= $min;
    });
    $this->addRule('between', function($val, $min, $max){
      if(static::checkRule('datetime', $val)) {
        $val = strtotime($val);
      }
      if(static::checkRule('datetime', $min)) {
        $min = strtotime($min);
      }
      if(static::checkRule('datetime', $max)) {
        $max = strtotime($max);
      }

      return static::checkRule('min', $val, $min)
        && static::checkRule('max', $val, $max);
    });

    // string
    $this->addRule('alpha', 'ctype_alpha');
    $this->addRule('alnum', 'ctype_alnum');
    $this->addRule('digit', 'ctype_digit');
    $this->addRule('hex', 'ctype_xdigit');
    $this->addRule('word', function($val){
      return static::checkRule('regex', $val, '/^[a-z-.]+$/i');
    });

    $this->addRule('length', function($val, $len){
      return strlen($val) == $len;
    });
    $this->addRule('lengthBetween', function($val, $min, $max){
      return static::checkRule('lengthMin', $val, $min) && static::checkRule('lengthMax', $val, $max);
    });
    $this->addRule('lengthMin', function($val, $min){
      return strlen($val) >= $min;
    });
    $this->addRule('lengthMax', function($val, $max){
      return strlen($val) <= $max;
    });
    $this->addRule('contains', function($val, $needle, $caseinsensitive = false){
      return ($caseinsensitive
        ? mb_stripos($val, $needle, 0, mb_detect_encoding($val))
        : mb_strpos($val, $needle, 0, mb_detect_encoding($val))
        ) !== false;
    });
    $this->addRule('startsWith', function($val, $needle, $caseinsensitive=false){
      if(is_array($val)) {
        return ($caseinsensitive
          ? strcasecmp(reset($val), $needle)
          : strcmp(reset($val), $needle)
          ) === 0;
      }
      return ($caseinsensitive
        ? mb_stripos($val, $needle, 0, mb_detect_encoding($val))
        : mb_strpos($val, $needle, 0, mb_detect_encoding($val))
        ) === 0;
    });
    $this->addRule('endsWith', function($val, $needle, $caseinsensitive=false){
      if(is_array($val)) {
        return ($caseinsensitive
          ? strcasecmp(end($val), $needle)
          : strcmp(end($val), $needle)
          ) === 0;
      }
      $last = strlen($val) - strlen($needle);
      return ($caseinsensitive
        ? mb_strripos($val, $needle, 0, mb_detect_encoding($val))
        : mb_strrpos($val, $needle, 0, mb_detect_encoding($val))
        ) === $last;
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
      return !!filter_var($val, FILTER_VALIDATE_EMAIL);
    });
    $this->addRule('url', function($val){
      return !!filter_var($val, FILTER_VALIDATE_URL);
    });
    $this->addRule('ip', function($val){
      return !!filter_var($val, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4|FILTER_FLAG_IPV6);
    });

    // datetime
    $this->addRule('datetime', function($val){
      if(@date_default_timezone_get())
        date_default_timezone_set('Asia/Shanghai');
      return !!strtotime($val);
    });
  }
}