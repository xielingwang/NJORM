<?php
/**
 * @Author: byamin
 * @Date:   2014-12-25 01:06:58
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-01-01 10:26:20
 */
use \NJORM\NJCom\NJGroupBy as NJGB;
use \NJORM\NJCom\NJCondition as NJCnd;
class NJOrderByTest extends PHPUnit_Framework_TestCase {
  function testOrder() {
    $o = new NJGB("field");
    $this->assertEquals('GROUP BY `field`', (string)$o);

    $o->desc("field2");
    $this->assertEquals('GROUP BY `field`, `field2` DESC', (string)$o);

    $o->add("1", false);
    $this->assertEquals('GROUP BY `field`, `field2` DESC, 1 DESC', (string)$o);

    $o->having(new NJCnd('field2', '3'));

    $this->assertEquals('GROUP BY `field`, `field2` DESC, 1 DESC HAVING `field2` = 3', (string)$o);
  }
}