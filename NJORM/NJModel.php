<?php
/**
 * @Author: Amin by
 * @Date:   2014-12-15 10:22:32
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-04-17 18:56:59
 */
namespace NJORM;
use \NJORM\NJSql\NJTable;
use \NJORM\NJQuery;
use \Countable, \ArrayAccess, \Iterator, \JsonSerializable; // INTERFACE

// Iterator, ArrayAccess, Countable, JsonSerializable
class NJModel implements Countable,ArrayAccess,JsonSerializable,Iterator {
  // data
  public $_table;
  protected $_data = array();
  private $_modified = array();
  private $_lazy_reload = false;

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

  public function isLazyReload(){
    return $this->_lazy_reload;
  }
  public function withLazyReload() {
    $this->_lazy_reload = true;
    return $this;
  }
  protected function lazyReload() {
    if($this->_lazy_reload) {
      $this->_lazy_reload = false;
      $prikey = $this->_table->primary();
      $model = (new NJQuery($this->_table))->where($prikey, $this[$prikey])->limit(1)->fetchOne();
      $this->_data = $model->_data;
    }
    return $this;
  }

  /**
   * setData: set list data for NJModel
   * 
   * C1.setData($key, $value)
   * C2.setData(array())
   */
  protected function setData() {
    // Case 1: two or more arguments, implements C1
    if(func_num_args() >= 2) {
      // TODO: check data
      $this->_data[func_get_arg(0)] = func_get_arg(1);
    }

    // Case 2: one argument, implements C2
    elseif(func_num_args() > 0) {
      $data = func_get_arg(0);
      $data = $this->_table->doPipeOut($data);
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

    // single value
    if(func_num_args() >= 2) {
      $k = func_get_arg(0);
      $v = func_get_arg(1);
      if(!array_key_exists($k, $this->_data) || $this->_data[$k] !== $v)
      $this->_modified[$k] = $v;
    }

    // multi-values
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
    $this->lazyReload();

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
    if(func_num_args() > 0 
      && ($modified = func_get_arg(0))
      && is_array($modified)) {
      $this->setModified($modified);
    }

    $tbname = $this->_table->getName();

    if(NJORM::inst()->$tbname
      ->where($this->_table->primary(), $this->pri_key_value())
      ->update($this->_modified)){
      $this->_data = array_merge($this->_data, $this->_modified);
      $this->_modified = array();
      $this->withLazyReload();
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
    $this->lazyReload();

    $relInfo = $this->_table->rel($name);

    return (new NJQuery($relInfo[0]))->setRelData(array(
      'rel' => $relInfo[1],
      'data' => $this[$relInfo[1]['sk']],
      ));
  }

  function __call($name, $arguments) {
    return call_user_func_array(array($this->$name, 'where'), $arguments);
  }

  /* JsonSerializable */
  public function jsonSerialize() {
    $this->lazyReload();
    if($this->_modified){
      return array_merge($this->_data, $this->_modified);
    }

    return $this->_data;
  }

  /* Countable */
  public function count() {
    $this->lazyReload();
    return count(array_merge($this->_data, $this->_modified));
  }

  /* ArrayAccess */
  public function offsetExists($offset) {
    $this->lazyReload();
    return array_key_exists($offset, $this->_data) 
    || array_key_exists($offset, $this->_modified);
  }
  public function offsetGet($offset){
    $this->lazyReload();
    return $this->getValue($offset);
  }
  public function offsetSet($offset, $value){
    $this->lazyReload();
    return $this->setModified($offset, $value);
  }
  public function offsetUnset($offset){
    $this->lazyReload();
    unset($this->_data[$offset]);
    unset($this->_modified[$offset]);
  }

  /**
   * Iterator
   */
  public function rewind(){
    $this->lazyReload();
    reset($this->_data);
  }
  
  public function current() {
    $this->lazyReload();
    $key = key($this->_data);
    if($key !== NULL && $key !== FALSE && $this->_modified
      && array_key_exists($key, $this->_modified) ) {
      return $this->_modified[$key];
    }

    return current($this->_data);
  }
  
  public function key(){
    $this->lazyReload();
    return key($this->_data);
  }
  
  public function next(){
    $this->lazyReload();
    next($this->_data);
    return $this->current();
  }
  
  public function valid(){
    $this->lazyReload();
    $key = key($this->_data);
    $var = ($key !== NULL && $key !== FALSE);
    return $var;
  }
}