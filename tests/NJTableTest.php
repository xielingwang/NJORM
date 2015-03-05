<?php
/**
 * @Author: byamin
 * @Date:   2015-02-02 23:46:24
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-05 11:42:14
 */
use \NJORM\NJSql\NJTable;
use \NJORM\NJSql\NJExpr;

class NJTableTest extends PHPUnit_Framework_TestCase {
  public function setUp() {
    if(!NJTable::defined('tuser')) {
      NJTable::define('longlong_tuser', 'tuser')
      ->primary('ID', 'id')
      ->field('username', 'un')
      ->field('password', 'pwd')
      ->field('last_login', 'll')
      ->field('create_at', 'ca');
    }
  }

  public function testColumns() {
    $table = NJTable::tuser();
    $njexpr = $table->columns('id,un,pwd,ll,create_at');
    $this->assertEquals('`ID` `id`,`username` `un`,`password` `pwd`,`last_login` `ll`,`create_at`', $njexpr->stringify());

    $njexpr = $table->columns(array('id,un,pwd,ll', new NJExpr('COUNT(*)'), array(new NJExpr('COUNT(*)'), 'ct')), 'tu');
    $this->assertEquals('`tu`.`ID` `id`,`tu`.`username` `un`,`tu`.`password` `pwd`,`tu`.`last_login` `ll`,COUNT(*),COUNT(*) `ct`', $njexpr->stringify());
    return $table;
  }

  public function testColumnsExcept() {
    $table = NJTable::tuser();
    $ret = $table->columnsWithout('pwd');
    $this->assertEquals('id,un,ll,ca', implode(',', $ret));

    $ret = $table->columnsWithout('pwd,last_login');
    $this->assertEquals('id,un,ca', implode(',', $ret));
    return $table;
  }

  public function testDefine1() {
    $rand = rand(100, 999);
    $tbname = 'table'.$rand;
    $alias = 'tb'.$rand;

    NJTable::define($tbname, $alias)
    ->fields([
      'ID' => 'id',
      'username' => 'un',
      'password' => 'pwd',
      'lastLogin' => 'll',
      'createAt' => 'ca',
      ], 'id');

    $table = NJTable::$alias();

    $njexpr = $table->columns('id,un,pwd,ll,createAt');
    $this->assertEquals('`ID` `id`,`username` `un`,`password` `pwd`,`lastLogin` `ll`,`createAt`', $njexpr->stringify());

    $njexpr = $table->columns(array('id,un,pwd,ll', new NJExpr('COUNT(*)'), array(new NJExpr('COUNT(*)'), 'ct')), 'tu');
    $this->assertEquals('`tu`.`ID` `id`,`tu`.`username` `un`,`tu`.`password` `pwd`,`tu`.`lastLogin` `ll`,COUNT(*),COUNT(*) `ct`', $njexpr->stringify());
  }

  public function testDefine2() {
    $rand = rand(100, 999);
    $tbname = 'table'.$rand;
    $alias = 'tb'.$rand;

    NJTable::define($tbname, $alias)
    ->fields('prefix', ['id','username','password','lastLogin','createAt'], 'id');

    $table = NJTable::$alias();

    $njexpr = $table->columns('id,username,password,lastLogin,createAt');
    $this->assertEquals('`prefix_id` `id`,`prefix_username` `username`,`prefix_password` `password`,`prefix_lastLogin` `lastLogin`,`prefix_createAt` `createAt`', $njexpr->stringify());

    $njexpr = $table->columns(array('id,username,password,lastLogin', new NJExpr('COUNT(*)'), array(new NJExpr('COUNT(*)'), 'ct')), 'tu');
    $this->assertEquals('`tu`.`prefix_id` `id`,`tu`.`prefix_username` `username`,`tu`.`prefix_password` `password`,`tu`.`prefix_lastLogin` `lastLogin`,COUNT(*),COUNT(*) `ct`', $njexpr->stringify());
  }
}
