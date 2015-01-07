<?php
/**
 * @Author: byamin
 * @Date:   2015-01-07 00:27:39
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-01-07 01:36:31
 */
namespace NJORM/NJCom;

class NJValid {
  protected static instance() {
    static $inst;
    if(!$inst) {
      $inst = new NJValid();
    }
    return $inst;
  }

  public static function register($rule) {
    self::instance()->addRule($rule);
  }

  public static function valid($rule, $value) {

  }

  public function addRule($rule) {

  }

  public function checkRule($rule) {

  }

  public function __construct() {
    $this->addRule('notEmpty', function($val){
      return !empty($val);
    });
    $this->addRule('in', 'in_array');
    $this->addRule('notIn', function($val, $arr){
      return !in_array($val, $arr);
    });
    $this->addRule('array', 'is_array');
    $this->addRule('integer', 'is_int');
    $this->addRule('accepted', function($val){
      $this->checkRule('in', strtolower(trim($val)), array('yes','1','on','true'));
    });
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