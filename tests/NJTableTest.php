<?php
/**
 * @Author: byamin
 * @Date:   2014-12-20 13:00:34
 * @Last Modified by:   Amin by
 * @Last Modified time: 2014-12-26 14:03:11
 */
use \NJORM\NJTable as NJTbl;
class NJTableTest extends PHPUnit_Framework_TestCase {
  public function testTableTest() {
    $tbl = new NJTbl('good');

    $this->assertEquals("CREATE TABLE `test_good`(\n`pk1` INT(11) unsigned NOT NULL DEFAULT 1 COMMENT '这是个注释',\n`pk2` INT(11),\n`longkey1` VARCHAR(256),\nPRIMARY KEY (`pk1`,`pk2`)\n);", (string)$tbl);

    $tbl->field('ct', 'created_time', 'INT(11) unsigned', '创建时间');
    $tbl->field('ut', 'updated_time', 'INT(11) unsigned', true, 0, '更新时间');
    $this->assertEquals("CREATE TABLE `test_good`(\n`pk1` INT(11) unsigned NOT NULL DEFAULT 1 COMMENT '这是个注释',\n`pk2` INT(11),\n`longkey1` VARCHAR(256),\n`created_time` INT(11) unsigned COMMENT '创建时间',\n`updated_time` INT(11) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',\nPRIMARY KEY (`pk1`,`pk2`)\n);", (string)$tbl);

    $this->assertEquals('SELECT `pk1` `sa1`, `pk2` `sa2`, `longkey1` `sa3`, `created_time` `ct`, `updated_time` `ut`', $tbl->select_star());

    echo (string)$tbl;
  }
}