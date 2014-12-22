<?php
/**
 * @Author: byamin
 * @Date:   2014-12-21 16:11:15
 * @Last Modified by:   byamin
 * @Last Modified time: 2014-12-21 21:25:47
 */
class NJWhereTest extends PHPUnit_Framework_TestCase {
  function testWhere() {
    $cond1 = new \NJORM\NJCondition("abc", ">", 3);
    $this->assertEquals("`abc` > '3'", $cond1->toString());

    $cond2 = new \NJORM\NJCond2ition("abc", 3);
    $this->assertEquals("`abc` = '3'", $cond2->toString());

    $cond3 = new \NJORM\NJCondition("1");
    $this->assertEquals("1", $cond3->toString());

    $cond4 = new \NJORM\NJCondition("`abc` = %s", 'eee');
    $this->assertEquals("`abc` = 'eee'", $cond4->toString());
  }
}