<?php
/**
 * @Author: byamin
 * @Date:   2014-12-25 01:06:58
 * @Last Modified by:   byamin
 * @Last Modified time: 2014-12-26 01:42:34
 */
use \NJORM\NJCom\NJOrderBy as NJOB;
class NJOrderTest extends PHPUnit_Framework_TestCase {
  function testOrder() {
    $o = new NJOB("field");
    $this->assertEquals('`field`', $o->toString());

    $o->add("field2", false);
    $this->assertEquals('`field`, `field2` DESC', $o->toString());

    $o->add("1", false);
    $this->assertEquals('`field`, `field2` DESC, 1 DESC', $o->toString());

    $this->assertEquals('ORDER BY `field`, `field2` DESC, 1 DESC', (string)$o);
  }
}