<?php
/**
 * @Author: AminBy
 * @Date:   2015-03-30 19:27:57
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-31 11:16:50
 */
namespace NJORM\NJSql;

class NJDefaults {
  protected $_ins_pl = array();
  protected $_upd_pl = array();

  function doit($data, $update) {
    if($update) {
      $_pls =& $this->_upd_pl;
    }
    else {
      $_pls =& $this->_ins_pl;
    }

    array_map(function($pipes, $col) use (&$data) {
      array_map(function($pipe) use (&$data, $col) {
        if(is_callable($pipe)) {
          $rv =& $data[$col];
          $data[$col] = $pipe($rv, $data);
        }
        elseif(!isset($data[$col])) {
          $data[$col] = $pipe;
        }
      }, $pipes);
    }, array_values($_pls), array_keys($_pls));

    return $data;
  }

  const TYPE_SET_INS = 0;
  const TYPE_SET_UPD = 1;
  const TYPE_SET_BTH = 2;
  public function set($col, $val, $type) {
    if(in_array($type, array(static::TYPE_SET_INS, static::TYPE_SET_BTH))) {
      $this->setIns($col, $val);
    }
    if(in_array($type, array(static::TYPE_SET_UPD, static::TYPE_SET_BTH))) {
      $this->setUpd($col, $val);
    }
    return $this;
  }
  protected function setIns($col, $val) {
    if(!array_key_exists($col, $this->_ins_pl)){
      $this->_ins_pl[$col] = array();
    }
    $this->_ins_pl[$col][] = $val;
  }
  protected function setUpd($col, $val) {
    if(!array_key_exists($col, $this->_upd_pl)){
      $this->_upd_pl[$col] = array();
    }
    $this->_upd_pl[$col][] = $val;
  }
}