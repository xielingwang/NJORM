<?php
/**
 * @Author: AminBy
 * @Date:   2015-03-25 14:04:29
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-25 15:27:34
 */

namespace NJORM;

class NJCheck {

  protected $_rule;
  protected $_rule_args;

  function __construct($rule, $args) {
    $this->_rule = $rule;
    $this->_rule_args = $args;
  }

  function __invoke($val) {
    $args = array_merge(array($this->_rule, $val), $this->_rule_args);
    return call_user_func_array(__NAMESPACE__.'\\NJValid::checkRule', $args);
  }
}