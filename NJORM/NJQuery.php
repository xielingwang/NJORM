<?php
/**
 * @Author: byamin
 * @Date:   2015-01-01 12:09:20
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-01-05 08:20:22
 */
namespace NJORM;
use \NJORM\NJCom\NJStringiable;
use \NJORM\NJCom\NJOrderby;

class NJQuery implements NJStringifiable{
  protected $table;

  public function __construct($table) {
    $this->table = $table;
  }

  protected $_select_arg = array();
  public function select() {
    $this->_select_arg = array_merge($this->_select_arg, func_get_args());
    return $this;
  }

  protected $_limit_arg = array();
  public function limit() {
    $this->_limit_arg = func_get_args();
    return $this;
  }

  protected $_where_arg = array();
  public function whereAnd() {
    $this->_where_arg = array_merge($this->_where_arg, array('AND', func_get_args()));
    return $this;
  }

  public function whereOr() {
    $this->_where_arg = array_merge($this->_where_arg, array('OR', func_get_args()));
    return $this;
  }

  protected $_order_by;
  public function sortAsc() {
    if(is_null($this->_order_by))
      $this->_order_by = new NJOrderby();

    foreach(func_get_args() as $field) {
      $this->_order_by->add($field, true);
    }
    return $this;
  }

  public function sortDesc() {
    if(is_null($this->_order_by))
      $this->_order_by = new NJOrderby();

    foreach(func_get_args() as $field) {
      $this->_order_by->add($field, false);
    }
    return $this;
  }

  public function stringify() {

  }
}