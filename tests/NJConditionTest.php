<?php
/**
 * @Author: byamin
 * @Date:   2014-12-21 16:11:15
 * @Last Modified by:   byamin
 * @Last Modified time: 2014-12-25 01:08:49
 */
use \NJORM\NJCondition as NJCnd;
class NJWhereTest extends PHPUnit_Framework_TestCase {
  function testCondition() {
    $cond1 = new NJCnd("abc", ">", 3);
    $this->assertEquals("`abc` > 3", $cond1->toString());

    $cond2 = new NJCnd("abc", 3);
    $this->assertEquals("`abc` = 3", $cond2->toString());

    $cond3 = new NJCnd("1");
    $this->assertEquals("1", $cond3->toString());

    $cond4 = new NJCnd("`abc` >= %s", 'eee');
    $this->assertEquals("`abc` >= 'eee'", $cond4->toString());
    return compact('cond1', 'cond2', 'cond3', 'cond4');
  }

  /**
   * @depends testCondition
   */
  function testComplexCondition($arg) {
    extract($arg);

    $conds_1 = NJCnd::N($cond1, $cond2);
    $this->assertEquals("`abc` > 3 AND `abc` = 3", $conds_1->toString());

    $conds_2 = NJCnd::O($cond1, $cond2);
    $this->assertEquals("`abc` > 3 OR `abc` = 3", $conds_2->toString());

    $conds_3 = NJCnd::N(array('eee', 5), $cond4, $conds_2);
    $this->assertEquals("`eee` = 5 AND `abc` >= 'eee' AND (`abc` > 3 OR `abc` = 3)", $conds_3->toString());

    $conds_4 = NJCnd::N(array('eee', 5), $cond4, $conds_1);
    $this->assertEquals("`eee` = 5 AND `abc` >= 'eee' AND `abc` > 3 AND `abc` = 3", $conds_4->toString());
  }

  function testAdvanceCondition1() {
    $cond = new NJCnd("field", null);
    $this->assertEquals('`field` IS NULL', $cond->toString());

    $cond = new NJCnd("field", true);
    $this->assertEquals('`field` IS TRUE', $cond->toString());

    $cond = new NJCnd("field", '!=', false);
    $this->assertEquals('`field` IS NOT FALSE', $cond->toString());

    $cond = new NJCnd("field", array());
    $this->assertEquals('`field` IS NULL', $cond->toString());

    $cond = new NJCnd("field", array(1,2,3,4));
    $this->assertEquals('`field` IN (1,2,3,4)', $cond->toString());

    $cond = new NJCnd("field", array(1,2,3,4,"s'3"));
    $this->assertEquals("`field` IN (1,2,3,4,'s\'3')", $cond->toString());
  }
  function testAdvanceCondition2() {
    $cond = new NJCnd("field", 'between', 3, 5);
    $this->assertEquals("`field` BETWEEN 3 AND 5", $cond->toString());

    $cond = new NJCnd("field", 'not   between', 3, 5);
    $this->assertEquals("`field` NOT BETWEEN 3 AND 5", $cond->toString());

    $cond = new NJCnd("field1", "`field2`");
    $this->assertEquals("`field1` = `field2`", $cond->toString());

    $cond = new NJCnd("field1", "like", "abc%");
    $this->assertEquals("`field1` LIKE 'abc%'", $cond->toString());
    $this->assertEquals("WHERE `field1` LIKE 'abc%'", $cond->toWhere());
  }
}