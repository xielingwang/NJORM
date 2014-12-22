<?php
/**
 * @Author: byamin
 * @Date:   2014-12-20 15:14:30
 * @Last Modified by:   byamin
 * @Last Modified time: 2014-12-21 21:33:50
 */
namespace NJORM;
class NJWhere {
  protected $_where = array();
  protected $_is_and = true;

  public function toString($enclose = false) {
    $where = array();
    foreach($this->_where as $w) {
      if($w instanceof NJWhere)
        $where[] = $w->toString(true);
      if($w instanceof NJCondition){
        $where[] = $w->toString();
      }
    }

    $op = $this_type == self::TYPE_AND ? " AND " : " OR ";
    return implode($op, $where);
  }

  protected function __construct($is_and, $args) {
    $this->_is_and = $is_and;
    $this->_where = $this->_conditions($args);
  }

  protected function _conditions($args) {
    $list = array();
    foreach($args as $arg) {
      $list[] = new NJCondition($arg);
    }
    return $list;
  }

  public static function and() {
    return new self(true, func_get_args());
  }

  public static function or() {
    return new self(false, func_get_args());
  }
}