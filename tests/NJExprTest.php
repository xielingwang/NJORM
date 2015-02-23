<?php
/**
 * @Author: AminBy
 * @Date:   2015-02-23 20:10:16
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-02-23 20:27:24
 */
use \NJORM\NJSql\NJExpr;
class NJExprTest extends PHPUnit_Framework_TestCase {
  function testNJExpr() {
    $njexpr = new NJExpr('unix_timestamp()');
    $this->assertEquals('unix_timestamp()', $njexpr->stringify());
    $this->assertEmpty($njexpr->parameters(), 'message');
  }

  function testNJExpr2() {
    $njexpr = new NJExpr('unix_timestamp(?, %s)', 'hello', 'hi');
    $this->assertEquals('unix_timestamp(?, \'hi\')', $njexpr->stringify());
    $this->assertNotEmpty($njexpr->parameters(), 'message');
    $params = $njexpr->parameters();
    $this->assertEquals(current($params), 'hello');
  }
}