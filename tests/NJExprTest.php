<?php
/**
 * @Author: AminBy
 * @Date:   2015-02-23 20:10:16
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-02-23 21:28:56
 */
use \NJORM\NJSql\NJObject;
use \NJORM\NJSql\NJExpr;
use \NJORM\NJSql\NJCondition as NJCnd;
class NJExprTest extends PHPUnit_Framework_TestCase {

  function testNJObject() {
    $njexpr = new NJExpr('unix_timestamp()');
    $this->assertTrue($njexpr instanceof NJObject, 'Njexpr is NJObject');
  }

  function testNJExpr() {
    $njexpr = new NJExpr('unix_timestamp()');
    $this->assertEquals('unix_timestamp()', $njexpr->stringify());
    $this->assertEmpty($njexpr->parameters(), 'message');
  }

  function testNJExpr2() {
    $njexpr = new NJExpr('unix_timestamp(?, %s)', 'hello', 'hi');
    $this->assertEquals('unix_timestamp(?, \'hi\')', $njexpr->stringify());
    $this->assertNotEmpty($njexpr->parameters(), 'parameters not empty');
    $params = $njexpr->parameters();
    $this->assertEquals(current($params), 'hello');
  }

  function testNJExprCondition() {

    $cnd = NJCnd::fact(array(
        'b' => 2,
        'c' => new NJExpr('unix_timestamp(?, %s)', 'hello', 'hi'),
        'd' => '2.33'
        ));
    $this->assertEquals("`b` = 2 AND `c` = unix_timestamp(?, 'hi') AND `d` = '2.33'", $cnd->stringify());

    $this->assertNotEmpty($cnd->parameters(), 'parameters not empty');
    $params = $cnd->parameters();
    $this->assertEquals(current($params), 'hello');
  }
}