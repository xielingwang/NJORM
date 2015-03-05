<?php
/**
 * @Author: AminBy
 * @Date:   2015-02-23 20:10:16
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-05 16:54:14
 */
use \NJORM\NJSql;
class NJExprTest extends PHPUnit_Framework_TestCase {

  function testNJExpr() {
    $njexpr = NJSql\NJExpr::fact('unix_timestamp()');
    $this->assertEquals('unix_timestamp()', $njexpr->stringify());
    $this->assertEmpty($njexpr->parameters(), 'message');
  }

  function testNJExpr2() {
    $njexpr = NJSql\NJExpr::fact('unix_timestamp(?, %s)', 'hello', 'hi');
    $this->assertEquals('unix_timestamp(?, \'hi\')', $njexpr->stringify());
    $this->assertNotEmpty($njexpr->parameters(), 'parameters not empty');
    $params = $njexpr->parameters();
    $this->assertEquals(current($params), 'hello');
  }

  function testNJExprCondition() {

    $cnd = NJSql\NJCondition::fact(array(
        'b' => 2,
        'c' => NJSql\NJExpr::fact('unix_timestamp(?, %s)', 'hello', 'hi'),
        'd' => '2.33'
        ));
    $this->assertEquals("`b` = 2 AND `c` = unix_timestamp(?, 'hi') AND `d` = '2.33'", $cnd->stringify());

    $this->assertNotEmpty($cnd->parameters(), 'parameters not empty');
    $params = $cnd->parameters();
    $this->assertEquals(current($params), 'hello');
  }

  function testNJExprSelect() {
    $rand = rand(100, 999);
    $tbname = 'table'.$rand;
    $alias = 'tb'.$rand;

    NJSql\NJTable::define($tbname, $alias)
    ->fields('prefix', ['id','user','pass','lastlog','reg'], 'id');

    $table = NJSql\NJTable::$alias();

    $njexpr = $table->columns('id,user,pass,lastlog,reg'
      , NJSql\NJExpr::fact('UPPER(?)', 'jiayou')
      // , NJSql\NJExpr::fact('UPPER(?)', 'jiayou')->as('upper')
      );
    $this->assertEquals('`prefix_id` `id`,`prefix_user` `un`,`prefix_pass` `pass`,`prefix_lastlog` `lastlog`,`prefix_reg` `reg`,UPPER(?),UPPER(?) `upper`', $njexpr->stringify());
  }
}