<?php

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