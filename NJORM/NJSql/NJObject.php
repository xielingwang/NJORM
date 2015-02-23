<?php
/**
 * @Author: AminBy
 * @Date:   2015-02-23 21:05:33
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-02-23 21:24:00
 */
namespace NJORM\NJSql;
abstract class NJObject {
  protected $_parameters = array();
  protected $_value;
  public function addParameters() {
    if(is_null($this->_parameters)){
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
    if(is_array($this->_value)) {
      $class = get_class($this);
      $params = array();
      foreach($this->_value as $cond) {
        if($cond instanceof $class) {
          $params = array_merge($params, $cond->parameters());
        }
      }
      return $params;
    }
    if($this->_value instanceof NJObject) {
      return $this->_value->parameters();
    }
    if(is_string($this->_value) or is_numeric($this->_value)) {
      return $this->_parameters;
    }
    trigger_error('unexpected type for condtion:' . gettype($this->_value));
    return $this->_parameters;
  }

  protected function _SetParameters() {
    $this->_parameters = array();
    return call_user_func_array(array($this, 'addParameters'), func_get_args());
  }

  protected function _SetValue($value) {
    $this->_value = $value;
    return $this;
  }

  public function stringify() {
    if($this->_value instanceof NJObject)
      return $this->_value->stringify();
    if(is_string($this->_value) || is_numeric($this->_value))
      return $this->_value;
  }
}