<?php
/**
 * @Author: Amin by
 * @Date:   2014-12-15 10:22:32
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-02-10 00:18:10
 */
if (!interface_exists('JsonSerializable')) {
  interface JsonSerializable {
    function jsonSerialize();
  }
}

// Iterator, ArrayAccess, Countable, JsonSerializable
abstract class NJModel extends Countable, JsonSerializable,ArrayAccess {
  // data
  protected $_table;
  protected $_data = array();
  protected $_modified = array();

  public function __construct($table) {
    if(is_string($table)) {
      $table = NJTable::$table();
    }
    $this->_table = $table;
  }

  public function setModified($key, $val) {
    $this->_modified[$key] = $val;
  }
  public function setData($key, $val) {
    $this->_data[$key] = $val;
  }
  public function getValue($key) {
    if(array_key_exists($key, $this->_modified))
      return $this->_modified[$key];
    elseif(array_key_exists($key, $this->_data))
      return $this->_data[$key];
    return null;
  }
  public function save() {

  }
  public function saved() {

  }

  /* Countable */
  public function count() {}

  /* JsonSerializable */
  public function jsonSerialize(){}

  /* ArrayAccess */
  public function offsetExists($offset) {}
  public function offsetGet($offset){}
  public function offsetSet($offset, $value){}
  public function offsetUnset($offset){}
}