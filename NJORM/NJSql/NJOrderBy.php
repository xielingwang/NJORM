<?php
/**
 * @Author: byamin
 * @Date:   2014-12-25 00:56:57
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-02-23 21:36:36
 */
namespace NJORM\NJSql;
class NJOrderBy extends NJObject{
  protected $_data = array();
  public function __construct($field = null, $order = 'asc') {
    if(func_num_args() <= 0)
      return;

    $order = (trim($order) == 'asc');
    call_user_func_array(array($this, 'add'), array($field, $order));
  }

  public function asc($field) {
    return $this->add($field, true);
  }

  public function desc($field) {
    return $this->add($field, false);
  }

  public function add($field, $asc = true){
    $this->_data[$field] = $asc;
    return $this;
  }

  protected function _formatFieldName($f) {
    return is_numeric($f) ? $f : '`' . $f . '`';
  }

  public function stringify() {
    $orders = array();
    foreach($this->_data as $field => $v) {
      $orders[] = $this->_formatFieldName($field) . (!$v ? ' DESC' : '');
    }
    return implode(', ', $orders);
  }

  public function __toString() {
    return "ORDER BY " . $this->stringify();
  }
}