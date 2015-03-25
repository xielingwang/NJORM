<?php
/**
 * @Author: AminBy
 * @Date:   2015-03-25 16:43:45
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-25 20:13:18
 */
namespace NJORM\NJValid;
use NJORM\NJException;

class NJDuang {
  protected $_domain;
  protected $_keysChecks = array();

  public function __construct() {
    if(func_num_args() > 0)
      $this->_domain = func_get_arg(0);
  }

  public function add($key) {
    if(!array_key_exists($key, $this->_keysChecks)) {
      $this->_keysChecks[$key] = array();
    }

    $checks = func_get_args();
    array_shift($checks);

    foreach($checks as $check) {
      is_array($check) or $check = array($check);
      $check = NJRule::VA($check);
      $this->_keysChecks[$key][] = $check;
    }

    return $this;
  }

  public function __invoke($data) {
    $e = new NJException('validation');

    foreach($this->_keysChecks as $key => $checks) {
      isset($data[$key]) || $data[$key] = null;

      foreach ($checks as $check) {
        if(!$check($data[$key])) {
          $e->addValidFailed($this->_domain, $key, $check->rule, $check->params, $data[$key]);
        }
      }
    }

    if(!$e->isEmpty())
      throw $e;
  }
}