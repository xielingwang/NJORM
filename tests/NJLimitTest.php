<?php
/**
 * @Author: byamin
 * @Date:   2014-12-20 13:00:34
 * @Last Modified by:   Amin by
 * @Last Modified time: 2015-02-13 17:20:17
 */
use \NJORM\NJSql\NJLimit as NJLMT;
class NJLimitTest extends PHPUnit_Framework_TestCase {
  public function testLimit() {
    $l = new NJLMT(1,3);
    $this->assertEquals("LIMIT 1,3", (string)$l);

    $l = new NJLMT();
    $l->limit(3)->offset(4);
    $this->assertEquals("LIMIT 3 OFFSET 4", (string)$l);

    $l = new NJLMT(4);
    $this->assertEquals("LIMIT 4", (string)$l);

    $l = new NJLMT();
    $l->limit(3);
    $this->assertEquals("LIMIT 3", (string)$l);
  }
}