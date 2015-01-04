<?php
/**
 * @Author: byamin
 * @Date:   2014-12-25 00:56:57
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-01-01 10:28:54
 */
namespace NJORM\NJCom;
class NJOrderBy {
  protected $_data = array();
  public function __construct($field, $order = 'asc') {
    $order = (trim($order) == 'asc');
    call_user_func_array(array($this, 'add'), array($field, $order));
  }

  protected function asc($field) {
    return $this->add($field, true);
  }

  protected function desc($field) {
    return $this->add($field, false);
  }

  protected function add($field, $asc = true){
    $this->_data[$field] = $asc;
    return $this;
  }

  protected function _field_standardize($f) {
    return is_numeric($f) ? $f : '`' . $f . '`';
  }

  public function toString() {
    $orders = array();
    foreach($this->_data as $field => $v) {
      $orders[] = $this->_field_standardize($field) . (!$v ? ' DESC' : '');
    }
    return implode(', ', $orders);
  }

  public function __toString() {
    return "ORDER BY " . $this->toString();
  }
}