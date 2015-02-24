<?php
/**
 * @Author: byamin
 * @Date:   2014-12-26 00:35:38
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-02-25 00:04:28
 */
namespace NJORM\NJSql;
class NJGroupBy extends NJOrderBy{
  protected $_condition;

  public function having($condition) {
    if(!($condition instanceof NJCondition)){
      $condition = NJCondition::fact(func_get_args());
    }
    elseif(func_num_args() <= 1) {
      if(is_array($condition))
        $condition = NJCondition::fact($condition);
    }
    else{
      $condition = call_user_func_array(__NAMESPACE__.'\NJCondition::fact', func_get_args());
    }
    $this->_condition = $condition;
    return $this;
  }

  public function __toString() {
    $str = "GROUP BY " . $this->stringify();
    if($this->_condition)
      $str .= " HAVING " . $this->_condition->stringify();
    return $str;
  }
}