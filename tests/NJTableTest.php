<?php
/**
 * @Author: byamin
 * @Date:   2015-02-02 23:46:24
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-02-03 00:28:49
 */
use \NJORM\NJCom\NJTable;

class NJTableTest extends PHPUnit_Framework_TestCase {
  public function testLimit() {
    $arr = array('a' => 'c', 'b' => 'e', 'd' => 's');
    echo array_search('e', $arr);
  }
}
