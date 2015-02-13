<?php
/**
 * @Author: byamin
 * @Date:   2014-12-25 01:06:58
 * @Last Modified by:   Amin by
 * @Last Modified time: 2015-02-13 17:41:44
 */
use \NJORM\NJSql\NJGroupBy as NJGB;
use \NJORM\NJSql\NJCondition as NJCnd;
class NJOrderByTest extends PHPUnit_Framework_TestCase {
  function testOrder() {
    $o = new NJGB("field");
    $this->assertEquals('GROUP BY `field`', (string)$o);

    $o->desc("field2");
    $this->assertEquals('GROUP BY `field`, `field2` DESC', (string)$o);

    $o->desc("1");
    $this->assertEquals('GROUP BY `field`, `field2` DESC, 1 DESC', (string)$o);

    $o->having('field2', 3);

    $this->assertEquals('GROUP BY `field`, `field2` DESC, 1 DESC HAVING `field2` = 3', (string)$o);
  }
}