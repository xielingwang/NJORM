<?php
/**
 * @Author: byamin
 * @Date:   2014-12-21 16:11:15
 * @Last Modified by:   byamin
 * @Last Modified time: 2014-12-24 08:07:48
 */
class NJWhereTest extends PHPUnit_Framework_TestCase {
  function testCondition() {
    $cond1 = new \NJORM\NJCondition("abc", ">", 3);
    $this->assertEquals("`abc` > 3", $cond1->toString());

    $cond2 = new \NJORM\NJCondition("abc", 3);
    $this->assertEquals("`abc` = 3", $cond2->toString());

    $cond3 = new \NJORM\NJCondition("1");
    $this->assertEquals("1", $cond3->toString());

    $cond4 = new \NJORM\NJCondition("`abc` >= %s", 'eee');
    $this->assertEquals("`abc` >= 'eee'", $cond4->toString());
    return compact('cond1', 'cond2', 'cond3', 'cond4');
  }

  /**
   * @depends testCondition
   */
  function testComplexCondition($arg) {
    extract($arg);

    $conds_1 = \NJORM\NJCondition::N($cond1, $cond2);
    $this->assertEquals("`abc` > 3 AND `abc` = 3", $conds_1->toString());

    $conds_2 = \NJORM\NJCondition::O($cond1, $cond2);
    $this->assertEquals("`abc` > 3 OR `abc` = 3", $conds_2->toString());

    $conds_3 = \NJORM\NJCondition::N(array('eee', 5), $cond4, $conds_2);
    $this->assertEquals("`eee` = 5 AND `abc` >= 'eee' AND (`abc` > 3 OR `abc` = 3)", $conds_3->toString());

    $conds_4 = \NJORM\NJCondition::N(array('eee', 5), $cond4, $conds_1);
    $this->assertEquals("`eee` = 5 AND `abc` >= 'eee' AND `abc` > 3 AND `abc` = 3", $conds_4->toString());
  }

  function testAdvanceCondition() {
    $cond = new \NJORM\NJCondition("field", null);
    $this->assertEquals('`field` IS NULL', $cond->toString());

    $cond = new \NJORM\NJCondition("field", true);
    $this->assertEquals('`field` IS TRUE', $cond->toString());

    $cond = new \NJORM\NJCondition("field", '!=', false);
    $this->assertEquals('`field` IS NOT FALSE', $cond->toString());

    $cond = new \NJORM\NJCondition("field", array());
    $this->assertEquals('`field` IS NULL', $cond->toString());

    $cond = new \NJORM\NJCondition("field", array(1,2,3,4));
    $this->assertEquals('`field` IN (1,2,3,4)', $cond->toString());
  }
}