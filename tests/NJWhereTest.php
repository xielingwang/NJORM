<?php
/**
 * @Author: byamin
 * @Date:   2014-12-21 16:11:15
 * @Last Modified by:   byamin
 * @Last Modified time: 2014-12-23 01:20:07
 */
class NJWhereTest extends PHPUnit_Framework_TestCase {
  function testWhere() {
    $cond1 = new \NJORM\NJCondition("abc", ">", 3);
    $this->assertEquals("`abc` > 3", $cond1->toString());

    $cond2 = new \NJORM\NJCondition("abc", 3);
    $this->assertEquals("`abc` = 3", $cond2->toString());

    $cond3 = new \NJORM\NJCondition("1");
    $this->assertEquals("1", $cond3->toString());

    $cond4 = new \NJORM\NJCondition("`abc` >= %s", 'eee');
    $this->assertEquals("`abc` >= 'eee'", $cond4->toString());

    $conds_1 = \NJORM\NJCondition::N($cond1, $cond2);
    print_r($conds_1->toString());
    $this->assertEquals("`abc` > 3 AND `abc` = 3", $conds_1->toString());

    $conds_2 = \NJORM\NJCondition::O($cond1, $cond2);
    $this->assertEquals("`abc` > 3 OR `abc` = 3", $conds_2->toString());

    $conds_3 = NJORM\NJCondition::N(array('eee', 5), $cond4, $conds_2);
    $this->assertEquals("`eee` = 5 AND `abc` >= 'eee' AND (`abc` > 3 OR `abc` = 3)", $conds_3->toString());
  }
}