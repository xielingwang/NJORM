<?php
/**
 * @Author: AminBy
 * @Date:   2015-03-25 16:43:45
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-06-01 15:54:08
 */
namespace NJORM\NJValid;
use NJORM\NJException;

class NJDuang {
  protected $_domain;
  protected $_keysChecks = array();
  protected $_keysNotEmpty = array();

  public function __construct() {
    if(func_num_args() > 0)
      $this->_domain = func_get_arg(0);
  }

  public function add($key) {
    if(!array_key_exists($key, $this->_keysChecks)) {
      $this->_keysChecks[$key] = array();
      $this->_keysNotEmpty[$key] = false;
    }

    $checks = func_get_args();
    array_shift($checks);

    foreach($checks as $check) {
      is_array($check) or $check = array($check);

      if(in_array('notEmpty', $check)) {
        $this->_keysNotEmpty[$key] = true;
      }

      $check = NJRule::VA($check);
      $this->_keysChecks[$key][] = $check;
    }

    return $this;
  }

  public function __invoke($data, $update) {
    $e = new NJException(NJException::TYPE_VALIDATION);

    foreach($this->_keysChecks as $key => $checks) {
      if(!isset($data[$key])) {
        if($update)
          continue;
        $data[$key] = '';
      }

      foreach ($checks as $check) {

        // empty value and can be empty: pass
        if((is_null($data[$key]) or $data[$key] === '') && !$this->_keysNotEmpty[$key])
          continue;

        // have value, check it
        if(!$check($data[$key])) {
          $e->addValidFailed($this->_domain, $key, $check->rule, $check->params, $data[$key]);
        }
      }
    }

    if(!$e->isEmpty())
      throw $e;
  }
}