<?php
/**
 * @Author: AminBy
 * @Date:   2015-03-24 17:27:30
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-24 20:13:56
 */

class NJException extends \Exception {
  protected $_message = [];

  const TYPE_SYST = 0;
  const TYPE_USER = 1;
  protected $type = 1;
  public function __construct($key, $type=1, $params = null) {
  }
}