<?php
/**
 * @Author: AminBy
 * @Date:   2015-03-25 14:04:29
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-25 19:59:57
 */

namespace NJORM\NJValid;

class NJCheck {

  protected $_rule;
  protected $_rule_args;

  function __construct($rule, $args) {
    $this->_rule = $rule;
    $this->_rule_args = $args;
  }

  function __invoke($val) {
    $args = array_merge(array($this->_rule, $val), $this->_rule_args);
    return call_user_func_array(__NAMESPACE__.'\\NJRule::checkRule', $args);
  }

  public function __get($name) {
    if($name == 'rule')
      return $this->_rule;
    if($name == 'params')
      return $this->_rule_args;
  }
}