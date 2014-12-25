<?php
/**
 * @Author: byamin
 * @Date:   2014-12-20 13:00:34
 * @Last Modified by:   byamin
 * @Last Modified time: 2014-12-26 02:04:28
 */
use \NJORM\NJCom\NJLimit as NJLMT;
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