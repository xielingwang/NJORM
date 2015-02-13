<?php
/**
 * @Author: byamin
 * @Date:   2015-02-02 23:46:24
 * @Last Modified by:   Amin by
 * @Last Modified time: 2015-02-13 18:33:10
 */
use \NJORM\NJSql\NJTable;

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
    $string = $table->columns('id,un,pwd,ll,create_at');
    $this->assertEquals('`ID` `id`,`username` `un`,`password` `pwd`,`last_login` `ll`,`create_at`', $string);

    $string = $table->columns('id,un,pwd,ll', 'tu');
    $this->assertEquals('`tu`.`ID` `id`,`tu`.`username` `un`,`tu`.`password` `pwd`,`tu`.`last_login` `ll`', $string);
    return $table;
  }
}
