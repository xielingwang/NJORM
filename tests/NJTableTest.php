<?php
/**
 * @Author: byamin
 * @Date:   2014-12-20 13:00:34
 * @Last Modified by:   Amin by
 * @Last Modified time: 2014-12-30 13:58:18
 */
use \NJORM\NJTable as NJTbl;
class NJTableTest extends PHPUnit_Framework_TestCase {
  public function testTableTest() {
    $tbl = new NJTbl('good');

    $tbl->field('field_1', 'f1')->type('int', 11, true)->notnull()->comment('这是个注释');
    $tbl->field('field_2', 'f2')->type('int', 11);
    $tbl->field('field_3', 'f3')->type('varchar', 255);
    $tbl->setPrimaryKey('field_1', 'field_2')->setAutoIncrement('field_1');

    $this->assertEquals("CREATE TABLE `test_good`(\n`field_1` INT(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '这是个注释',\n`field_2` INT(11),\n`field_3` VARCHAR(255),\nPRIMARY KEY (`field_1`,`field_2`)\n);", $tbl->toDefine());


    $tbl->field('created_time', 'ct')->type('int', 11, true)->comment('创建时间');
    $tbl->field('updated_time', 'ut')->type('int', 11, true)->comment('更新时间')->default(0)->notnull();
    $this->assertEquals("CREATE TABLE `test_good`(\n`field_1` INT(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '这是个注释',\n`field_2` INT(11),\n`field_3` VARCHAR(255),\n`created_time` INT(11) unsigned COMMENT '创建时间',\n`updated_time` INT(11) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',\nPRIMARY KEY (`field_1`,`field_2`)\n);", $tbl->toDefine());

    $this->assertEquals('SELECT `field_1` `f1`,`field_2` `f2`,`field_3` `f3`,`created_time` `ct`,`updated_time` `ut`', $tbl->select_star());

    $this->assertEquals('SELECT `t1`.`field_1` `f1`,`t1`.`field_2` `f2`,`t1`.`field_3` `f3`,`t1`.`created_time` `ct`,`t1`.`updated_time` `ut`', $tbl->select_star('t1'));
  }
}