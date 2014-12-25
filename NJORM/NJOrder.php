<?php
/**
 * @Author: byamin
 * @Date:   2014-12-25 00:56:57
 * @Last Modified by:   byamin
 * @Last Modified time: 2014-12-25 01:14:17
 */
namespace NJORM;
class NJOrder {
  $_data = array();
  public function __construct($field, $asc = true) {
    call_user_func_array(array($this, 'add'), func_get_args());
  }

  public function add($field, $asc = true){
    $this->_data[$field] => $asc;
  }

  protected function _field_standardize($f) {
    return '`' . $f . '`';
  }

  public function toString() {
    $orders = array();
    foreach($this->_data as $field => $v) {
      $orders[] = $this->_field_standardize($field) . (!$v ? ' DESC' : '');
    }
    return $str;
  }

  public function toOrderBy() {
    return "ORDER BY " . $this->toString();
  }
}