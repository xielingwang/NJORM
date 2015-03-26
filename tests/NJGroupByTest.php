<?php
/**
 * @Author: byamin
 * @Date:   2014-12-25 01:06:58
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-26 16:33:20
 */
use \NJORM\NJSql\NJGroupBy as NJGB;
use \NJORM\NJSql\NJCondition as NJCnd;
class NJGroupByTest extends PHPUnit_Framework_TestCase {
  function testGroupBy() {
    $o = new NJGB("field");
    $this->assertEquals('GROUP BY `field`', $o->stringify());

    $o->desc("field2");
    $this->assertEquals('GROUP BY `field`, `field2` DESC', $o->stringify());

    $o->desc("1");
    $this->assertEquals('GROUP BY `field`, `field2` DESC, 1 DESC', $o->stringify());

    $o->having('field2', 3);

    $this->assertEquals('GROUP BY `field`, `field2` DESC, 1 DESC HAVING `field2` = 3', $o->stringify());
  }

  function testGroupBy2() {
    $o = (new NJGB("field"))
        ->desc("field2")
        ->desc("1")
        ->having(NJCnd::fact('field2', 3),NJCnd::fact('field', '>', 2));

    $this->assertEquals('GROUP BY `field`, `field2` DESC, 1 DESC HAVING `field2` = 3 AND `field` > 2', $o->stringify());
  }
}