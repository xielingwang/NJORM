<?php
/**
 * @Author: byamin
 * @Date:   2015-02-17 19:56:26
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-02-17 20:01:26
 */

class NJExpr {
  protected $_value;
  protected $_parameters;
  function __construct($value) {
    $this->_value = $value;
    $this->_parameters = func_get_args();
    array_shift($this->_parameters);
  }

  function stringify() {
    return $this->_value;
  }

  function parameters() {
    return $this->_parameters;
  }
}