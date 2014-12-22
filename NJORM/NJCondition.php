<?php
/**
 * @Author: byamin
 * @Date:   2014-12-21 16:51:57
 * @Last Modified by:   byamin
 * @Last Modified time: 2014-12-21 21:15:49
 */
namespace NJORM;
class NJCondition {
  protected $_condition;
  protected $_arg;
  function __construct() {
    $arg = $this->_arg = func_get_args();

    if(!count($arg)) {
      throw new InvalidArgumentException("Condition Arguments Empty!");
    }

    if(strpos($arg[0], '%') !== false) {
      $arg[0] = str_replace('%s', "'%s'", $arg[0]);
      $this->_condition = call_user_func_array('sprintf', $arg);
      return $this;
    }

    if(count($arg) > 2){
      $this->_condition = sprintf("`%s` %s '%s'", $arg[0], $arg[1], $arg[2]);
    }
    elseif(count($arg) > 1) {
      $this->_condition = sprintf("`%s` = '%s'", $arg[0], $arg[1]);
    }
    else {
      $this->_condition = $arg[0];
    }
    return $this;
  }

  function toString() {
    return $this->_condition;
  }
}