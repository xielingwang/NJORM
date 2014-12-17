<?php
/**
 * @Author: Amin by
 * @Date:   2014-12-15 10:22:32
 * @Last Modified by:   Amin by
 * @Last Modified time: 2014-12-17 18:16:32
 */

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