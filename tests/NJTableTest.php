<?php
/**
 * @Author: byamin
 * @Date:   2015-02-02 23:46:24
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-02-04 01:03:46
 */
use \NJORM\NJTable;

class NJTableTest extends PHPUnit_Framework_TestCase {
  public function testColumns() {
    $table = NJTable::define('user', 'tu')
    ->primary('ID', 'id')
    ->field('username', 'un')
    ->field('password', 'pwd')
    ->field('last_login', 'll')
    ->field('create_at', 'ca');

    $string = $table->columns('id,un,pwd,ll');
    $this->assertEquals('`ID` `id`,`username` `un`,`password` `pwd`,`last_login` `ll`', $string);

    $string = $table->columns('id,un,pwd,ll', 'tu');
    $this->assertEquals('`tu`.`ID` `id`,`tu`.`username` `un`,`tu`.`password` `pwd`,`tu`.`last_login` `ll`', $string);
    return $table;
  }

}
