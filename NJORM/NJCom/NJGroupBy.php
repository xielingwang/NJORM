<?php
/**
 * @Author: byamin
 * @Date:   2014-12-26 00:35:38
 * @Last Modified by:   Amin by
 * @Last Modified time: 2015-02-11 15:31:26
 */
namespace NJORM\NJCom;
class NJGroupBy extends NJOrderBy{
  protected $_condition;

  public function having($condition) {
    if(is_array($condition))
      $condition = NJCondition::fact($condition);
    elseif(!($condition instanceof NJCondition)){
      $condition = NJCondition::fact(func_get_args());
    }
    $this->_condition = $condition;
  }

  public function __toString() {
    $str = "GROUP BY " . parent::stringify();
    if($this->_condition)
      $str .= " HAVING " . $this->_condition->stringify();
    return $str;
  }
}