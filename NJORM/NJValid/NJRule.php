<?php
/**
 * @Author: byamin
 * @Date:   2015-01-07 00:27:39
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-05-19 15:16:32
 */
namespace NJORM\NJValid;
use NJORM\NJSql;

class NJRule {
  protected $_rules = array();
  protected $_messages = array();
  protected $_prev_rule; // save rule name after add rule

  protected static function instance() {
    static $inst;
    if(!$inst) {
      $inst = new static();
    }
    return $inst;
  }

  public static function messages() {
    return static::instance()->_messages;
  }

  public static function register($rule, $callable) {
    static::instance()->addRule($rule, $callable);
  }

  public static function VA($args) {
    $rule = array_shift($args); // $rule

    return new NJCheck($rule, $args);
  }

  public static function V($rule) {
    return static::VA(func_get_args());
  }

  public function addRule($rule, $callable) {
    if(!is_callable($callable)) {
      trigger_error('Argument 2 expects a callable value for NJRule::addRule()');
    }
    $this->_rules[$rule] = $callable;

    $this->_prev_rule = $rule;
    return $this;
  }

  public function msg($msg) {
    $this->_messages[$this->_prev_rule] = $msg;
    return $this;
  }

  public static function checkRule() {
    $self = static::instance();

    $args = func_get_args();
    $rule = array_shift($args);

    if(!array_key_exists($rule, $self->_rules)) {
      trigger_error("Rule {$rule} not found!");
    }

    return call_user_func_array($self->_rules[$rule], $args);
  }

  public function __construct() {
    // rule 'notEmpty'
    $this->addRule('notEmpty', function($val){
      return !empty($val);
    })->msg('"{v}" must not empty');

    // rule 'in'
    $this->addRule('in', function($val, $arr, $caseinsensitive=false){
      if(!is_array($arr)) {
        trigger_error('Argument 2 expects an array for rule "in/notIn"');
      }
      $regex = "/^{$val}$/";
      if($caseinsensitive)
        $regex .= 'i';
      return !!preg_grep($regex, $arr);
    })->msg('"{v}" must be in array {p}');

    // rule 'notIn'
    $this->addRule('notIn', function($val, $arr, $caseinsensitive=false){
      return !static::checkRule('in', $val, $arr, $caseinsensitive);
 
    })->msg('"{v}" must not in array {p}');

    // rule 'array'
    $this->addRule('array', 'is_array')->msg('"{v}" must be an array');

    // rule 'integer'
    $this->addRule('integer', function($val){
      return is_numeric($val) && ''.intval($val) == $val;
     })->msg('"{v}" must be an integer number');

    // rule 'float'
    $this->addRule('float', function($val){
      return !!filter_var($val, FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_THOUSAND);
     })->msg('"{v}" must be a float number');

    // rule 'true'
    $this->addRule('true', function($val, $strict = false){
      $options = null;
      $strict && $options = FILTER_NULL_ON_FAILURE;
      $ret = filter_var($val, FILTER_VALIDATE_BOOLEAN, $options);
      if($ret === NULL) {
        trigger_error(sprintf('"%s" is an invalid boolean value.', $val));
      }
      return $ret;
    })->msg('"{v}" must be a valid boolean value: true,on,yes,1');

    // rule 'numeric'
    $this->addRule('numeric', 'is_numeric')
    ->msg('"{v}" must be an numeric value');

    // rule 'positive'
    $this->addRule('positive', function($val){
      return is_numeric($val) && $val >= 0;
    })->msg('"{v}" must be a positive number');

    // rule 'negative'
    $this->addRule('negative', function($val){
      return !self::checkRule('positive', $val) && $val;
    })->msg('"{v}" must be a negative number');

    // rule 'max'
    $this->addRule('max', function($val, $max){
      return $val <= $max;
    })->msg('"{v}" mustn\'t greater than {p}');

    // rule 'min'
    $this->addRule('min', function($val, $min){
      return $val >= $min;
    })->msg('"{v}" mustn\'t less than {p}');

    // rule 'between'
    $this->addRule('between', function($val, $min, $max){
      return static::checkRule('min', $val, $min)
        && static::checkRule('max', $val, $max);
    })->msg('"{v}" must between {p} and {p2}');

    // string
    // rule 'alpha'
    $this->addRule('alpha', 'ctype_alpha')
    ->msg('"{v}" must consit of alpha chars');

    // rule 'alnum'
    $this->addRule('alnum', 'ctype_alnum')
    ->msg('"{v}" must consit of alpha and digit chars');

    // rule 'digit'
    $this->addRule('digit', 'ctype_digit')
    ->msg('"{v}" must consit of digit chars');

    // rule 'hex'
    $this->addRule('hex', 'ctype_xdigit')
    ->msg('"{v}" must be a valid hex string');

    // rule 'word'
    $this->addRule('word', function($val){
      return static::checkRule('regex', $val, '/^[a-z-.]+$/i');
    })->msg('"{v}" must be a valid word');

    // rule 'length'
    $this->addRule('length', function($val, $len){
      $vlen = function_exists('mb_strlen')
        ? mb_strlen($val)
        : strlen($val);
      return $vlen == $len;
    })->msg('"{v}"\'s length must equal of {p}');

    // rule 'lengthBetween'
    $this->addRule('lengthBetween', function($val, $min, $max){
      return static::checkRule('lengthMin', $val, $min) && static::checkRule('lengthMax', $val, $max);
    })->msg('"{v}"\'s length must between {p} and {p2}');

    // rule 'lengthMin'
    $this->addRule('lengthMin', function($val, $min){
      $vlen = function_exists('mb_strlen')
        ? mb_strlen($val)
        : strlen($val);
      return $vlen >= $min;
    })->msg('"{v}"\'s length must greater or equal than {p}');

    // rule 'lengthMax'
    $this->addRule('lengthMax', function($val, $max){
      $vlen = function_exists('mb_strlen')
        ? mb_strlen($val)
        : strlen($val);
      return $vlen <= $max;
    })->msg('"{v}"\'s length must less or equal than {p}');

    // rule 'contains'
    $this->addRule('contains', function($val, $needle, $caseinsensitive = false){
      if(function_exists('mb_detect_encoding')) {
        $encoding = mb_detect_encoding($val);
        $encoding || $encoding = 'utf-8';
        return ($caseinsensitive
          ? mb_stripos($val, $needle, 0, $encoding)
          : mb_strpos($val, $needle, 0, $encoding)
          ) !== false;
      }
      else {
        return ($caseinsensitive
          ? stripos($val, $needle)
          : strpos($val, $needle)
          ) !== false;
      }
    })->msg('"{v}" must contains "{p}"');

    // rule 'startsWith'
    $this->addRule('startsWith', function($val, $needle, $caseinsensitive=false){
      if(is_array($val)) {
        return ($caseinsensitive
          ? strcasecmp(reset($val), $needle)
          : strcmp(reset($val), $needle)
          ) === 0;
      }
      if(function_exists('mb_detect_encoding')) {
        $encoding = mb_detect_encoding($val);
        $encoding || $encoding = 'utf-8';
        return ($caseinsensitive
          ? mb_stripos($val, $needle, 0, $encoding)
          : mb_strpos($val, $needle, 0, $encoding)
          ) === 0;
      }
      else {
        return ($caseinsensitive
          ? stripos($val, $needle)
          : strpos($val, $needle)
          ) === 0;
      }
    })->msg('"{v}" must start with "{p}"');

    // rule 'endsWith'
    $this->addRule('endsWith', function($val, $needle, $caseinsensitive=false){
      if(is_array($val)) {
        return ($caseinsensitive
          ? strcasecmp(end($val), $needle)
          : strcmp(end($val), $needle)
          ) === 0;
      }
      if(function_exists('mb_strlen'))
        $last = mb_strlen($val) - mb_strlen($needle);
      else
        $last = strlen($val) - strlen($needle);

      if(function_exists('mb_detect_encoding')) {
        $encoding = mb_detect_encoding($val);
        $encoding || $encoding = 'utf-8';
        return ($caseinsensitive
          ? mb_strripos($val, $needle, 0, $encoding)
          : mb_strrpos($val, $needle, 0, $encoding)
          ) === $last;
      }
      else {
        return ($caseinsensitive
          ? strripos($val, $needle)
          : strrpos($val, $needle)
          ) === $last;
      }
    })->msg('"{v}" must end with {p}');

    // regex
    // rule 'regex'
    $this->addRule('regex', function($val, $pattern){
      $ret = preg_match($pattern, $val);
      if($ret === false){
        trigger_error('A regex error occurs: "' . $pattern . '"');
      }
      return !!$ret;
    })->msg('"{v}" must accord with regular pattern "{p}"');

    // rule 'email'
    $this->addRule('email', function($val){
      return !!filter_var($val, FILTER_VALIDATE_EMAIL);
    })->msg('"{v}" must be a valid email');

    // rule 'url'
    $this->addRule('url', function($val){
      return !!filter_var($val, FILTER_VALIDATE_URL);
    })->msg('"{v}" must be a valid URL');

    // rule 'ip'
    $this->addRule('ip', function($val){
      return !!filter_var($val, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4|FILTER_FLAG_IPV6);
    })->msg('"{v}" must be a valid IP address');

    // NJSql/NJTable
    // rule 'existed'
    $this->addRule('existed', function($val, $col, $table, $extra = null) {
      $table = NJSql\NJTable::factory($table);
      $query = new \NJORM\NJQuery($table);

      // extra conditions
      if($extra && is_array($extra)) {
        if(!is_array(current($extra)))
          $extra = array($extra);
        foreach($extra as $cond) {
          call_user_func_array(array($query, 'where'), $cond);
        }
      }

      // support multi primary keys
      if(is_array($col)) {
        return $query->where(array_combine($col, $val))->count() > 0;
      }

      // single primary key
      return $query->where($col, $val)->count() > 0;
    })->msg('"{v}" must be existed in {p2}.{p}, extra: {p3}');

    // rule 'notExisted'
    $this->addRule('notExisted', function($val, $col, $table, $extra = null){
      return !static::checkRule('existed', $val, $col, $table, $extra);
    })->msg('"{v}" must not be existed in {p2}.{p}, extra: {p3}');

    // rule 'unqiue'
    $this->addRule('unique', function($val, $col, $table, $extra = null){
      return !static::checkRule('existed', $val, $col, $table, $extra);
    })->msg('{p2}.{p} should be unique, "{v}" has been existed before, extra: {p3}');

    // datetime
    /*
    // rule 'dateBetween'
    $this->addRule('dateBetween', function($val, $min, $max){
      if(!is_numeric($val)) {
        $val = strtotime($val);
      }
      if(!is_numeric($min)) {
        $min = strtotime($min);
      }
      if(!is_numeric($max)) {
        $max = strtotime($max);
      }
      return static::checkRule('between', $val, $min, $max);
    });

    // rule 'datetime'
    $this->addRule('datetime', function($val, $format=null){
      if(@date_default_timezone_get())
        date_default_timezone_set('Asia/Shanghai');

      $valid = false;
      if(is_null($format)) {
        $format = array(DATE_ATOM, DATE_COOKIE, DATE_ISO8601, DATE_RFC822 , DATE_RFC850, DATE_RFC1036, DATE_RFC1123, DATE_RFC2822, DATE_RFC3339, DATE_RSS, DATE_W3C);
      }
      else {
        $format = array($format);
      }
      foreach($format as $fmt) {
        try {
          date_create_from_format($fmt, $val);
          $valid = true;
        }
        catch(Exception $e) {
        }
        if($valid) break;
      }
      return $valid;
    });
    */
  }
}