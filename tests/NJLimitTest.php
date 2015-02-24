<?php
/**
 * @Author: byamin
 * @Date:   2014-12-20 13:00:34
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-02-24 23:38:42
 */
use \NJORM\NJSql\NJLimit as NJLMT;
class NJLimitTest extends PHPUnit_Framework_TestCase {
  public function testLimit() {
    $l = new NJLMT(1,3);
    $this->assertEquals("LIMIT 1,3", $l->stringify());

    $l = new NJLMT();
    $l->limit(3)->offset(4);
    $this->assertEquals("LIMIT 3 OFFSET 4", $l->stringify());

    $l = new NJLMT(4);
    $this->assertEquals("LIMIT 4", $l->stringify());

    $l = new NJLMT();
    $l->limit(3);
    $this->assertEquals("LIMIT 3", $l->stringify());

    $l = NJLMT::factory()->offset(12)->limit(4);
    $this->assertEquals("LIMIT 4 OFFSET 12", $l->stringify());

    $l = NJLMT::factory()->limit(12, 3)->limit(4);
    $this->assertEquals("LIMIT 12,4", $l->stringify());
  }
}