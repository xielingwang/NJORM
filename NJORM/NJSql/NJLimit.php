<?php
/**
 * @Author: byamin
 * @Date:   2014-12-26 01:41:57
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-02-24 23:42:20
 */
namespace NJORM\NJSql;
// TODO: extends NJObject
class NJLimit extends NJExpr {
  protected $_limit = 1;
  protected $_offset = 0;
  protected $_isOffset = false;

  public static function factory(){
    $inst = new NJLimit;
    if(func_num_args()>0) {
      $args = func_get_arg(1);
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

  protected function setValueComma(){
    $this->_SetValue(sprintf('LIMIT %s%d', $this->_offset?($this->_offset.','):'', $this->_limit));
  }

  public function setValueOffset() {
    $this->_SetValue(sprintf('LIMIT %d%s', $this->_limit, $this->_offset?(' OFFSET '.$this->_offset):''));
  }

  public function limit() {
    if(func_num_args() > 1) {
      $this->_offset = intval(func_get_arg(0));
      $this->_limit = intval(func_get_arg(1));
      $this->setValueComma();
    }
    else {
      $this->_limit = intval(func_get_arg(0));
      $this->_isOffset
        ? $this->setValueOffset()
        : $this->setValueComma();
    }

    return $this;
  }

  public function offset($offset) {
    $this->_isOffset = true;
    $this->_offset = intval($offset);
    $this->setValueOffset();
    return $this;
  }

  public function parameters() {
    return array();
  }
}