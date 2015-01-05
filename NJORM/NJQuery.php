<?php
/**
 * @Author: byamin
 * @Date:   2015-01-01 12:09:20
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-01-06 01:45:01
 */
namespace NJORM;
use \NJORM\NJCom\NJStringifiable;
use \NJORM\NJCom\NJOrderby;

class NJQuery implements NJStringifiable{
  protected $_table;

  public function __construct($table) {
    $this->_table = $table;
  }

  protected $_select_arg = array('*');
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
  public function where() {
    $this->_where_arg = array_merge($this->_where_arg, func_get_args());
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
    $string = call_user_func_array(array($this->_table, 'select'), $this->_select_arg);
    $string .= ' FROM ' . $this->_table->name;

    if($this->_where_arg) {
      $string .= ' ' . call_user_func_array(__NAMESPACE__.'\NJCom\NJCondition::N', $this->_where_arg)->toString();
    }

    if($this->_order_by) {
      $string .= ' ' . $this->_order_by->toString();
    }

    if($this->_limit_arg) {
      $string .= ' ' . call_user_func_array(__NAMESPACE__.'\NJCom\NJLimit::factory', $this->_limit_arg);
    }
    return $string;
  }
}