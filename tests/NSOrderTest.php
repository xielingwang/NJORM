<?php
/**
 * @Author: byamin
 * @Date:   2014-12-25 01:06:58
 * @Last Modified by:   Amin by
 * @Last Modified time: 2014-12-25 21:38:03
 */
use \NJORM\NJOrder as NJOrd;
class NJWhereTest extends PHPUnit_Framework_TestCase {
  function testOrder() {
    $o = new NJOrd("field");
    $this->assertEquals('`field`', $o->toString());

    $o->add("field2", false);
    $this->assertEquals('`field`, `field2` DESC', $o->toString());
  }
}