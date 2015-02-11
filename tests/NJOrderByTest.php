<?php
/**
 * @Author: byamin
 * @Date:   2014-12-25 01:06:58
 * @Last Modified by:   Amin by
 * @Last Modified time: 2015-02-11 15:26:21
 */
use \NJORM\NJCom\NJOrderBy as NJOB;
class NJOrderTest extends PHPUnit_Framework_TestCase {
  function testOrder() {
    $o = new NJOB("field");
    $this->assertEquals('`field`', $o->stringify());

    $o->add("field2", false);
    $this->assertEquals('`field`, `field2` DESC', $o->stringify());

    $o->add("1", false);
    $this->assertEquals('`field`, `field2` DESC, 1 DESC', $o->stringify());

    $this->assertEquals('ORDER BY `field`, `field2` DESC, 1 DESC', (string)$o);
  }
}