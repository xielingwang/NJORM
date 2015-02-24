<?php
/**
 * @Author: byamin
 * @Date:   2015-02-02 23:46:24
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-02-25 00:59:09
 */
use \NJORM\NJSql\NJTable;
use \NJORM\NJSql\NJExpr;

class NJTableTest extends PHPUnit_Framework_TestCase {
  public function setUp() {
    if(!NJTable::defined('tuser')) {
      NJTable::define('tuser')
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
}
