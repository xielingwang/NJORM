<?php
/**
 * @Author: Amin by
 * @Date:   2014-12-15 10:22:32
 * @Last Modified by:   Amin by
 * @Last Modified time: 2015-02-27 10:30:11
 */
namespace NJORM;
use \NJORM\NJSql\NJTable;
use \NJORM\NJQuery;
use \Countable, \ArrayAccess;

// Iterator, ArrayAccess, Countable, JsonSerializable
class NJModel implements Countable, ArrayAccess {
  // data
  protected $_table;
  protected $_data = array();
  private $_modified = array();

  public function __construct($table, $data=null) {
    // set table
    if(!($table instanceof NJTable)) {
      $table = NJTable::$table();
    }
    $this->_table = $table;

    // set collection data
    if($data) {
      $this->setData($data);
    }
  }

  /**
   * setData: set list data for NJModel
   * 
   * C1.setData(array(array(),array(),...))
   * C2.setData(array($njmodel,$njmodel,...))
   * C3.setData($offset, $njmodel)
   * C4.setData($offset, array())
   */
  protected function setData() {
    // Case 1: two or more arguments, implements C3/C4
    if(func_num_args() >= 2) {
      // TODO: check data
      $this->_data[func_get_arg(0)] = func_get_arg(1);
    }

    // Case 2: one argument, implements C1/C2
    elseif(func_num_args() > 0) {
      $data = func_get_arg(0);
      if(is_array($data)) {
        $this->_data = array();
        foreach($data as $k => $v) {
          $this->setData($k, $v);
        }
      }
      else {
        trigger_error('Expects an array for NJModel::setData()!');
      }
    }

    // Case 3: none of arguments, what a pity
    else {
      trigger_error('Expects at least one arguments for NJModel::setData()');
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

  public function pri_key_value() {
    return $this[$this->_table->primary()];
  }

  public function save() {
    $tbname = $this->_table->getName();
    $this->_modified[$this->_table->primary] = $this->pri_key_value();
    if(NJORM::inst()->$tbname->update($this->_modified)){
      $this->_data = array_merge($this->_data, $this->_modified);
      $this->_modified = array();
    }
    return $this;
  }
  public function delete() {
    $tbname = $this->_table->getName();
    $primary = $this->_table->primary();
    $ret = NJORM::inst()->$tbname->where($primary, $this[$primary])->delete();
    $this->setData(array());
    return $ret;
  }
  public function saved() {
    return empty($this->_modified);
  }

  // __get
  function __get($name) {
    $rel = $this->_table->rel($name);
    switch($rel['type']) {
      case NJTable::TYPE_RELATION_ONE:
      return $this->getRelOne($rel, $name);
      break;
      case NJTable::TYPE_RELATION_MANY:
      return $this->getRelMany($rel, $name);
      break;
      case NJTable::TYPE_RELATION_MANY_X:
      return $this->getRelManyX($rel, $name);
      break;
    }
  }

  function __call($name, $arguments) {
    return call_user_func_array(array($this->$name, 'where'), $arguments);
  }
  function getRelOne($rel, $table) {
    return (new NJQuery($table))->where($rel['fk'], $this[$rel['sk']])->limit(1);
  }
  function getRelMany($rel, $table) {
    return (new NJQuery($table))->where($rel['fk'], $this[$rel['sk']]);
  }
  function getRelManyX($rel, $table) {
    
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
      unset($this->_data[$offset]);
    }
    if(array_key_exists($offset, $this->_modified)){
      unset($this->_modified[$offset]);
    }
  }
}