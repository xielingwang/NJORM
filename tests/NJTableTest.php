<?php
/**
 * @Author: byamin
 * @Date:   2014-12-20 13:00:34
 * @Last Modified by:   byamin
 * @Last Modified time: 2014-12-30 07:45:07
 */
use \NJORM\NJTable as NJTbl;
class NJTableTest extends PHPUnit_Framework_TestCase {
  public function testTableTest() {
    $tbl = new NJTbl('good');

    $tbl->field('field_1')->type('int', 11, true)->notnull()->default(1)->comment('这是个注释');
    $tbl->field('field_2')->type('int', 11);
    $tbl->field('field_3')->type('varchar', 256);
    $tbl->setPrimaryKey('field_1', 'field_2')->setAutoIncrement('field_1');

    $this->assertEquals("CREATE TABLE `test_good`(\n`field_1` INT(11) unsigned NOT NULL DEFAULT 1 COMMENT '这是个注释',\n`field_2` INT(11),\n`field_3` VARCHAR(256),\nPRIMARY KEY (`field_1`,`field_2`)\n);", (string)$tbl);

    $tbl->field('ct', 'created_time', 'INT(11) unsigned', '创建时间');
    $tbl->field('ut', 'updated_time', 'INT(11) unsigned', true, 0, '更新时间');
    $this->assertEquals("CREATE TABLE `test_good`(\n`field_1` INT(11) unsigned NOT NULL DEFAULT 1 COMMENT '这是个注释',\n`field_2` INT(11),\n`field_3` VARCHAR(256),\n`created_time` INT(11) unsigned COMMENT '创建时间',\n`updated_time` INT(11) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',\nPRIMARY KEY (`field_1`,`field_2`)\n);", (string)$tbl);

    $this->assertEquals('SELECT `field_1` `sf1`,`field_2` `sf2`,`field_3` `sf3`,`created_time` `ct`,`updated_time` `ut`', $tbl->select_star());

    $this->assertEquals('SELECT `t1`.`field_1` `sf1`,`t1`.`field_2` `sf2`,`t1`.`field_3` `sf3`,`t1`.`created_time` `ct`,`t1`.`updated_time` `ut`', $tbl->select_star('t1'));
  }
}