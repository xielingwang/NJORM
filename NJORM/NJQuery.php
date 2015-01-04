<?php
/**
 * @Author: byamin
 * @Date:   2015-01-01 12:09:20
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-01-03 01:11:12
 */
namespace NJORM;

class NJQuery{
  protected $table;

  public function __construct($table) {
    $this->table = $table;
  }
}