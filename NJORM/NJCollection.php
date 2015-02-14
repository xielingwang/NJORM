<?php
/**
 * @Author: byamin
 * @Date:   2015-02-14 11:57:17
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-02-14 14:25:09
 */
namespace NJORM;
use \NJORM\NJSql\NJTable;
use \NJORM\NJQuery;
use \Countable, \ArrayAccess;

// Iterator, ArrayAccess, Countable, JsonSerializable
class NJCollection implements Countable, ArrayAccess {
  // data
  protected $_table;
  protected $_list = array();

  public function __construct($table, $data=null) {
    if(is_string($table)) {
      $table = NJTable::$table();
    }
    $this->_table = $table;
    if($data) {
      $this->setData($data);
    }
  }

  protected function setData() {
    if(func_num_args() >= 2) {
      $val = func_get_arg(1);
      if(is_array($val)) {
        $val = new NJModel($this->_table, $val);
      }
      if($val instanceof NJModel) {
        $this->_list[func_get_arg(0)] = $val;
      }
      else {
        trigger_error('Argument 2 expects an array or a NJModel for NJCollection::setData()');
      }
    }
    else {
      $data = func_get_arg(0);
      if(is_array($data)) {
        $this->_list = array();
        foreach($data as $k => $v) {
          $this->setData($k, $v);
        }
      }
      else {
        trigger_error('Expects an array for NJCollection::setData()');
      }
    }
    return $this;
  }
  protected function getValue($key) {
    if(array_key_exists($key, $this->_list))
      return $this->_list[$key];
    trigger_error(sprintf('Undefined index "%s" in model!', $key));
  }
  public function save() {

  }
  public function saved() {
    foreach($this->_list as $model) {
      if(!$model->saved())
        return false;
    }
    return true;
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
    return (new NJQuery($table))->where($rel['fk'], $this[$rel['sk']])->limit($this->count());
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
    return count($this->_list);
  }

  /* ArrayAccess */
  public function offsetExists($offset) {
    return array_key_exists($offset, $this->_list);
  }
  public function offsetGet($offset){
    if(is_numeric($offset))
      return $this->getValue($offset);
    $arr = array();
    foreach($this->_list as $model)
      $arr[] = $model[$offset];
    return $arr;
  }
  public function offsetSet($offset, $value){
    trigger_error('It is no way to add any model for NJCollection!');
  }
  public function offsetUnset($offset){
    if(array_key_exists($offset, $this->_list)){
      unset($this->_list[$offset]);
    }
  }
}