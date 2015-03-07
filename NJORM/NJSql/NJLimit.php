<?php
/**
 * @Author: byamin
 * @Date:   2014-12-26 01:41:57
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-07 18:16:03
 */
namespace NJORM\NJSql;
// TODO: extends NJObject
class NJLimit extends NJExpr {
  protected $_limit = 1;
  protected $_offset = 0;
  protected $_isOffsetType = false;

  public static function factory(){
    $inst = new NJLimit;
    if(func_num_args()>0) {
      $args = func_get_arg(0);
      is_array($args) || $args = func_get_args();
      call_user_func_array(array($inst, 'limit'), $args);
    }
    return $inst;
  }

  public function __construct() {
    if(func_num_args() > 0) {
      call_user_func_array(array($this, 'limit'), func_get_args());
    }
  }

  public function stringify() {
    if(func_num_args() > 0) {
      return ($this->_isOffsetType || !$this->_offset)
      ? 'LIMIT ' . $this->_limit
      : 'LIMIT ' . $this->_offset;
    }
    return parent::stringify();
  }

  protected function _updateValueWithComma(){
    $this->_SetValue(sprintf('LIMIT %s%d', $this->_offset?($this->_offset.','):'', $this->_limit));
  }

  public function _updateValueWithOffset() {
    $this->_SetValue(sprintf('LIMIT %d%s', $this->_limit, $this->_offset?(' OFFSET '.$this->_offset):''));
  }

  public function limit() {
    if(func_num_args() > 1) {
      $this->_offset = intval(func_get_arg(0));
      $this->_limit = intval(func_get_arg(1));
      $this->_updateValueWithComma();
    }
    elseif(func_num_args() > 0) {
      $this->_limit = intval(func_get_arg(0));
      $this->_isOffsetType
        ? $this->_updateValueWithOffset()
        : $this->_updateValueWithComma();
    }
    else {
      return (int)$this->_limit;
    }

    return $this;
  }

  public function offset() {
    if(func_num_args() > 0) {
      $offset = func_get_arg(0);
      $this->_isOffsetType = true;
      $this->_offset = intval($offset);
      $this->_updateValueWithOffset();
      return $this;
    }
    else {
      return (int)$this->_offset;
    }
  }

  public function parameters() {
    return array();
  }
}