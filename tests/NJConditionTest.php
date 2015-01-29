<?php
/**
 * @Author: byamin
 * @Date:   2014-12-21 16:11:15
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-01-30 01:33:54
 */
use \NJORM\NJCom\NJCondition as NJCnd;
class NJConditionTest extends PHPUnit_Framework_TestCase {
  function testConditionParse() {
    $cond = new NJCnd();
    $cond->parse(array("abc", ">", 3));
    $this->assertEquals("`abc` > 3", $cond->stringify());

    $cond->parse(array("1"));
    $this->assertEquals("1", $cond->stringify());
  }
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
  }

  /**
   * @depends testCondition
   */
  function testComplexCondition() {
    $cond1 = NJCnd::fact('abc', '>', 3);
    $cond2 = NJCnd::fact('abc', '=', 3);
    $cond3 = NJCnd::fact('abc', '>=', 'eee');

    $conds_1 = NJCnd::factX($cond1,$cond2);// NJCnd::fact(, $cond2);
    $this->assertEquals("`abc` > 3 AND `abc` = 3", $conds_1->stringify());

    $conds_2 = NJCnd::factX($cond1, 'or', $cond2);
    $this->assertEquals("`abc` > 3 OR `abc` = 3", $conds_2->stringify());

    $conds_3 = NJCnd::factX(array('eee', 5), $cond3, $conds_2);
    $this->assertEquals("`eee` = 5 AND `abc` >= 'eee' AND (`abc` > 3 OR `abc` = 3)", $conds_3->stringify());

    $conds_4 = NJCnd::factX(array('eee', 5), $cond3, $conds_1);
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

  function testConditionWithBindParameters() {
    $cond1 = NJCnd::fact("`field` = ?", "good");
    $this->assertEquals("`field` = ?", $cond1->stringify());
    $this->assertEmpty(array_diff($cond1->parameters(), array("good")));

    $cond2 = NJCnd::fact("`key` IN (?,?,?)", 1, 'true', 'on');
    $this->assertEquals("`key` IN (?,?,?)", $cond2->stringify());
    $this->assertEmpty(array_diff($cond2->parameters(), array(1, 'true', 'on')));

    $cond_1 = NJCnd::factX($cond1, $cond1);
    $this->assertEquals("`field` = ? AND `key` IN (?,?,?)", $cond_1->stringify());
    $this->assertEmpty(array_diff($cond_1->parameters(), array('good', 1, 'true', 'on')));

    $cond3 = NJCnd::fact('`key` = %d OR `val` = ?', 3, 9);
    $this->assertEquals('`key` = 3 OR `val` = ?', $cond3->stringify());
    $this->assertEmpty(array_diff($cond3->parameters(), array(9)));

    $cond3 = NJCnd::fact('`key` = %d AND `val` = ? OR `key` = %d AND `val` = ?', 3, 9, 7, '6');
    $this->assertEquals('`key` = 3 AND `val` = ? OR `key` = 7 AND `val` = ?', $cond3->stringify());
    $this->assertEmpty(array_diff($cond3->parameters(), array(9)));
  }
}