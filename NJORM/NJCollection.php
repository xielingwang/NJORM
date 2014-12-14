<?php

class NJCollection extends IteratorAggregate,Countable, JsonSerializable,ArrayAccess{
  protected $_data = array();
  public function count() {
    return count($this->_data);
  }

  /* ArrayAccess */
  public offsetExists($offset) {}
  public offsetGet($offset){}
  public offsetSet($offset, $value){}
  public offsetUnset($offset){}

  /* IteratorAggregate */
  public function getIterator() {
    return new ArrayIterator($this->_data);
  }
}