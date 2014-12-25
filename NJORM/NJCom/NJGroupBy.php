<?php
/**
 * @Author: byamin
 * @Date:   2014-12-26 00:35:38
 * @Last Modified by:   byamin
 * @Last Modified time: 2014-12-26 01:42:24
 */
namespace NJORM\NJCom;
class NJGroupBy extends NJOrderBy{
  protected $_condition;

  public function having($condition) {
    $this->_condition = $condition;
  }

  public function __toString() {
    $str = "GROUP BY " . parent::toString();
    if($this->_condition)
      $str .= " HAVING " . $this->_condition->toString();
    return $str;
  }
}