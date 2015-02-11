<?php
/**
 * @Author: Amin by
 * @Date:   2014-12-15 10:22:32
 * @Last Modified by:   Amin by
 * @Last Modified time: 2015-02-11 15:47:53
 */
namespace NJORM;

if (!interface_exists('JsonSerializable')) {
  interface JsonSerializable {
    function jsonSerialize();
  }
}

// Iterator, ArrayAccess, Countable, JsonSerializable
class NJModel implements \Countable,\JsonSerializable,\ArrayAccess {
  // data
  protected $_table;
  protected $_data = array();
  protected $_modified = array();

  public function __construct($table, $data=null) {
    if(is_string($table)) {
      $table = NJTable::$table();
    }
    $this->_table = $table;
    if(is_array($data)) {
      $this->setData($data);
    }
    else {
      trigger_error('NJModel::data set error!');
    }
  }

  protected function setData() {
    if(func_num_args() >= 2) {
      // NJTable::check_field_exist($this->_table,func_get_arg(0));
      $this->_data[func_get_arg(0)] = func_get_arg(1);
    }
    else {
      $data = func_get_arg(0);
      if(is_array($data)) {
        $this->_data = array();
        foreach($data as $k => $v) {
          $this->setData($k, $v);
        }
      }
      else {
        trigger_error('NJModel::_set error!');
      }
    }
    return $this;
  }
  protected function setModified() {
    if(func_num_args() >= 2) {
      $this->_modified[func_get_arg(0)] = func_get_arg(1);
    }
    else {
      $data = func_get_arg(0);
      if(is_array($data)) {
        $this->_modified = array();
        foreach($data as $k => $v) {
          $this->setModified($k, $v);
        }
      }
      else {
        trigger_error('NJModel::setModified error!');
      }
    }
    return $this;
  }
  protected function getValue($key) {
    if(array_key_exists($key, $this->_modified))
      return $this->_modified[$key];
    elseif(array_key_exists($key, $this->_data))
      return $this->_data[$key];
    trigger_error(sprintf('Undefined index "%s" in model!', $key));
  }
  public function save() {

  }
  public function saved() {

  }

  /* JsonSerializable */
  public function jsonSerialize(){}

  /* Countable */
  public function count() {
    return count(array_merge($this->_data, $this->_modified));
  }

  /* ArrayAccess */
  public function offsetExists($offset) {
    return array_key_exists($offset, $this->_data) 
    || array_key_exists($offset, $this->_modified);
  }
  public function offsetGet($offset){
    return $this->getValue($offset);
  }
  public function offsetSet($offset, $value){
    return $this->setModified($offset, $value);
  }
  public function offsetUnset($offset){
    if(array_key_exists($offset, $this->_data)){
      unset($offset, $this->_data);
    }
    if(array_key_exists($offset, $this->_modified)){
      unset($offset, $this->_modified);
    }
  }
}