<?php
/**
 * @Author: byamin
 * @Date:   2014-12-21 16:11:15
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-01-13 01:16:12
 */
use \NJORM\NJCom\NJCondition as NJCnd;
class NJConditionTest extends PHPUnit_Framework_TestCase {
  function testCondition() {
    $cond = NJCnd::fact("abc", ">", 3);
    $this->assertEquals("`abc` > 3", $cond->stringify());

    $cond->or("abc", 3);
    $this->assertEquals("`abc` > 3 OR `abc` = 3", $cond->stringify());

    $cond->close()->and("1");
    $this->assertEquals("(`abc` > 3 OR `abc` = 3) AND 1", $cond->stringify());

    $cond->and("`abc` is NULL");
    $this->assertEquals("(`abc` > 3 OR `abc` = 3) AND 1 AND `abc` is NULL", $cond->stringify());

    $cond = NJCnd::fact("`abc` >= %s", 'eee');
    $this->assertEquals("`abc` >= 'eee'", $cond->stringify());

    return compact('cond1', 'cond2', 'cond', 'cond4', 'cond5');
  }

  /**
   * @depends testCondition
   */
  function testComplexCondition($arg) {
    extract($arg);

    $conds_1 = NJCnd::fact($cond1, $cond2);
    $this->assertEquals("`abc` > 3 AND `abc` = 3", $conds_1->stringify());

    $conds_2 = NJCnd::fact($cond1, 'or', $cond2);
    $this->assertEquals("`abc` > 3 OR `abc` = 3", $conds_2->stringify());

    $conds_3 = NJCnd::fact(array('eee', 5), $cond4, $conds_2);
    $this->assertEquals("`eee` = 5 AND `abc` >= 'eee' AND (`abc` > 3 OR `abc` = 3)", $conds_3->stringify());

    $conds_4 = NJCnd::N(array('eee', 5), $cond4, $conds_2);
    $this->assertEquals("`eee` = 5 AND `abc` >= 'eee' AND `abc` > 3 AND `abc` = 3", $conds_4->stringify());
  }

  function testAdvanceCondition1() {
    $cond = NJCnd::fact("field", null);
    $this->assertEquals('`field` IS NULL', $cond->stringify());

    $cond = NJCnd::fact("field", true);
    $this->assertEquals('`field` IS TRUE', $cond->stringify());

    $cond = NJCnd::fact("field", '!=', false);
    $this->assertEquals('`field` IS NOT FALSE', $cond->stringify());

    $cond = NJCnd::fact("field", array());
    $this->assertEquals('`field` IS NULL', $cond->stringify());

    $cond = NJCnd::fact("field", array(1,2,3,4));
    $this->assertEquals('`field` IN (1,2,3,4)', $cond->stringify());

    $cond = NJCnd::fact("field", array(1,2,3,4,"s'3"));
    $this->assertEquals("`field` IN (1,2,3,4,'s\'3')", $cond->stringify());
  }
  function testAdvanceCondition2() {
    $cond = NJCnd::fact("field", 'between', 3, 5);
    $this->assertEquals("`field` BETWEEN 3 AND 5", $cond->stringify());

    $cond = NJCnd::fact("field", 'not   between', 3, 5);
    $this->assertEquals("`field` NOT BETWEEN 3 AND 5", $cond->stringify());

    $cond = NJCnd::fact("field1", "`field2`");
    $this->assertEquals("`field1` = `field2`", $cond->stringify());

    $cond = NJCnd::fact("field1", "like", "abc%");
    $this->assertEquals("`field1` LIKE 'abc%'", $cond->stringify());
    $this->assertEquals("WHERE `field1` LIKE 'abc%'", (string)$cond);
  }
}