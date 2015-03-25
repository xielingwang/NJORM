<?php
/**
 * @Author: byamin
 * @Date:   2015-01-07 00:27:39
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-25 16:27:02
 */
namespace NJORM;
use NJORM\NJSql;

class NJValid {
  protected $rules = array();

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

  public static function V($rule) {

    $args = func_get_args();
    array_shift($args); // $rule

    return new NJCheck($rule, $args);
  }

  public function addRule($rule, $callable) {
    if(!is_callable($callable)) {
      trigger_error('Argument 2 expects a callable value for NJValid::addRule()');
    }
    $this->rules[$rule] = $callable;
  }

  public static function checkRule() {
    $self = static::instance();

    $args = func_get_args();
    $rule = array_shift($args);

    if(!array_key_exists($rule, $self->rules)) {
      trigger_error("Rule {$rule} not found!");
    }

    return call_user_func_array($self->rules[$rule], $args);
  }

  public function __construct() {
    // rule 'notEmpty'
    $this->addRule('notEmpty', function($val){
      return !empty($val);
    });

    // rule 'in'
    $this->addRule('in', function($val, $arr, $caseinsensitive=false){
      if(!is_array($arr)) {
        trigger_error('Argument 2 expects an array for rule "in/notIn"');
      }
      $regex = "/^{$val}$/";
      if($caseinsensitive)
        $regex .= 'i';
      return !!preg_grep($regex, $arr);
    });

    // rule 'notIn'
    $this->addRule('notIn', function($val, $arr, $caseinsensitive=false){
      return !static::checkRule('in', $val, $arr, $caseinsensitive);
 
    });
    // rule 'array'
    $this->addRule('array', 'is_array');

    // rule 'integer'
    $this->addRule('integer', function($val){
      return !!filter_var($val, FILTER_VALIDATE_INT, FILTER_FLAG_ALLOW_THOUSAND);
     });

    // rule 'float'
    $this->addRule('float', function($val){
      return !!filter_var($val, FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_THOUSAND);
     });

    // rule 'true'
    $this->addRule('true', function($val, $strict = false){
      $options = null;
      $strict && $options = FILTER_NULL_ON_FAILURE;
      $ret = filter_var($val, FILTER_VALIDATE_BOOLEAN, $options);
      if($ret === NULL) {
        trigger_error(sprintf('"%s" is an invalid boolean value.', $val));
      }
      return $ret;
    });

    // rule 'numeric'
    $this->addRule('numeric', 'is_numeric');

    // rule 'positive'
    $this->addRule('positive', function($val){
      return is_numeric($val) && $val > 0;
    });

    // rule 'negative'
    $this->addRule('negative', function($val){
      return !self::checkRule('positive', $val) && $val;
    });

    // rule 'max'
    $this->addRule('max', function($val, $max){
      return $val <= $max;
    });

    // rule 'min'
    $this->addRule('min', function($val, $min){
      return $val >= $min;
    });

    // rule 'between'
    $this->addRule('between', function($val, $min, $max){
      return static::checkRule('min', $val, $min)
        && static::checkRule('max', $val, $max);
    });

    // string
    // rule 'alpha'
    $this->addRule('alpha', 'ctype_alpha');

    // rule 'alnum'
    $this->addRule('alnum', 'ctype_alnum');

    // rule 'digit'
    $this->addRule('digit', 'ctype_digit');

    // rule 'hex'
    $this->addRule('hex', 'ctype_xdigit');

    // rule 'word'
    $this->addRule('word', function($val){
      return static::checkRule('regex', $val, '/^[a-z-.]+$/i');
    });

    // rule 'length'
    $this->addRule('length', function($val, $len){
      return strlen($val) == $len;
    });

    // rule 'lengthBetween'
    $this->addRule('lengthBetween', function($val, $min, $max){
      return static::checkRule('lengthMin', $val, $min) && static::checkRule('lengthMax', $val, $max);
 
    });
    // rule 'lengthMin'
    $this->addRule('lengthMin', function($val, $min){
      return strlen($val) >= $min;
    });

    // rule 'lengthMax'
    $this->addRule('lengthMax', function($val, $max){
      return strlen($val) <= $max;
    });

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
    });

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
    });

    // rule 'endsWith'
    $this->addRule('endsWith', function($val, $needle, $caseinsensitive=false){
      if(is_array($val)) {
        return ($caseinsensitive
          ? strcasecmp(end($val), $needle)
          : strcmp(end($val), $needle)
          ) === 0;
      }
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
    });

    // regex
    // rule 'regex'
    $this->addRule('regex', function($val, $pattern){
      $ret = preg_match($pattern, $val);
      if($ret === false){
        trigger_error('A regex error occurs: "' . $pattern . '"');
      }
      return !!$ret;
    });

    // rule 'email'
    $this->addRule('email', function($val){
      return !!filter_var($val, FILTER_VALIDATE_EMAIL);
    });

    // rule 'url'
    $this->addRule('url', function($val){
      return !!filter_var($val, FILTER_VALIDATE_URL);
    });

    // rule 'ip'
    $this->addRule('ip', function($val){
      return !!filter_var($val, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4|FILTER_FLAG_IPV6);
    });

    // NJSql/NJTable
    // rule 'existed'
    $this->addRule('existed', function($val, $col, $table, $extra = null) {
      $table = NJSql\NJTable::factory($table);
      $query = new NJQuery($table);

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
    });

    // rule 'notExisted'
    $this->addRule('notExisted', function($val, $col, $table, $extra = null){
      return !static::checkRule('existed', $val, $col, $table, $extra);
    });

    // rule 'unique'
    $this->addRule('unique', function($val, $col, $table, $extra = null){
      return !static::checkRule('existed', $val, $col, $table, $extra);
    });

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