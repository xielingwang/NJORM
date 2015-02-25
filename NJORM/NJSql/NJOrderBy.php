<?php
/**
 * @Author: byamin
 * @Date:   2014-12-25 00:56:57
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-02-25 22:20:20
 */
namespace NJORM\NJSql;
use NJORM\NJMisc;
class NJOrderBy extends NJExpr{
  protected $_data = array();
  public function __construct($field = null, $order = 'desc') {
    if(func_num_args() <= 0)
      return;

    if(!is_bool($order)) {
      $order = (trim($order) == 'desc');
    }
    $this->add($field, $order);
  }

  public function asc($field) {
    return $this->add($field, true);
  }

  public function desc($field) {
    return $this->add($field, false);
  }

  public function add($field, $asc = true){
    $this->_data[$field] = $asc;

    // set value
    $orders = array_map(function($field, $direction){
      return sprintf('%s%s', NJMisc::wrapGraveAccent($field), $direction?'':' DESC');
    }, array_keys($this->_data), array_values($this->_data));
    $this->_SetValue(implode(', ', $orders));

    return $this;
  }

  public function stringify() {
    return "ORDER BY " . parent::stringify();
  }
}