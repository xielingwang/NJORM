<?php
/**
 * @Author: byamin
 * @Date:   2015-02-14 11:57:17
 * @Last Modified by:   Amin by
 * @Last Modified time: 2015-03-04 20:09:57
 */
namespace NJORM;
use \NJORM\NJSql\NJTable;
use \NJORM\NJQuery;
use \Countable, \ArrayAccess;

// Iterator, ArrayAccess, Countable, JsonSerializable
class NJCollection extends NJModel implements Countable, ArrayAccess {

  /**
   * setData: set list data for NJCollection
   * 
   * C1.setData(array(array(),array(),...))
   * C2.setData(array($njmodel,$njmodel,...))
   * C3.setData($offset, $njmodel)
   * C4.setData($offset, array())
   */
  protected function setData() {
    // Case 1: two or more arguments, implements C3/C4
    if(func_num_args() >= 2) {
      $val = func_get_arg(1);
      if(is_array($val)) {
        $val = new NJModel($this->_table, $val);
      }
      if($val instanceof NJModel) {
        $this->_data[func_get_arg(0)] = $val;
      }
      else {
        trigger_error('Argument 2 expects an array or a NJModel for NJCollection::setData($offset, $array/$njmodel)');
      }
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
        trigger_error('Expects an array for NJCollection::setData($list)');
      }
    }

    // Case 3: none of arguments, what a pity
    else {
      trigger_error('Expects at least one arguments for NJCollection::setData()');
    }

    return $this;
  }

  /**
   * get list item with offset
   * if not exists triiger an error
   * 
   * @param  string $offset 
   * @return mixed NJModel or null
   */
  protected function getValue($offset) {
    if(array_key_exists($offset, $this->_data))
      return $this->_data[$offset];
    trigger_error(sprintf('Undefined index "%s" in model!', $key));
  }
  public function save() {

  }
  public function saved() {
    foreach($this->_data as $model) {
      if(!$model->saved())
        return false;
    }
    return true;
  }

  /* JsonSerializable */
  public function jsonSerialize(){}

  /* Countable */
  public function count() {
    return count($this->_data);
  }

  /* ArrayAccess */
  public function offsetExists($offset) {
    return array_key_exists($offset, $this->_data);
  }

  /**
   * ArrayAccess::offsetGet
   * $collection[$offset] when offset is digits returns instance of NJModel
   * $collection[$offset] when offset is a string returns an array with elements
   * which are the values of NJModel for $offset
   * 
   * @param  mixed $offset
   * @return mixed
   */
  public function offsetGet($offset){
    // return model
    if(is_int($offset))
      return $this->getValue($offset);

    // return an array with the values in $model where offset is $offset
    $arr = array();
    foreach($this->_data as $model)
      $arr[] = $model[$offset];
    return $arr;
  }
  /**
   * $collection[$offset] = "";
   * when can not set a new model for a NJCollection
   * 
   * @param  [type] $offset [description]
   * @param  [type] $value  [description]
   * @return [type]         [description]
   */
  public function offsetSet($offset, $value){
    trigger_error('It is no way to add any model for NJCollection!');
  }
  /**
   * unset($collection[$offset])
   * @param  [type] $offset [description]
   * @return [type]         [description]
   */
  public function offsetUnset($offset){
    if(array_key_exists($offset, $this->_data)){
      unset($this->_data[$offset]);
    }
  }
}