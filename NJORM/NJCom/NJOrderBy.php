<?php
/**
 * @Author: byamin
 * @Date:   2014-12-25 00:56:57
 * @Last Modified by:   byamin
 * @Last Modified time: 2014-12-26 01:42:14
 */
namespace NJORM\NJCom;
class NJOrderBy {
  protected $_data = array();
  public function __construct($field, $asc = true) {
    call_user_func_array(array($this, 'add'), func_get_args());
  }

  public function add($field, $asc = true){
    $this->_data[$field] = $asc;
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