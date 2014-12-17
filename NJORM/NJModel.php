<?php
/**
 * @Author: Amin by
 * @Date:   2014-12-15 10:22:32
 * @Last Modified by:   Amin by
 * @Last Modified time: 2014-12-17 18:16:15
 */
if (!interface_exists('JsonSerializable')) {
  interface JsonSerializable {
    function jsonSerialize();
  }
}

// Iterator, ArrayAccess, Countable, JsonSerializable
abstract class NJModel extends Countable, JsonSerializable,ArrayAccess {
  // data
  protected $_data = array();
  protected $_modified = array();

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