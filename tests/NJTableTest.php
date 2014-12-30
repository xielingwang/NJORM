<?php
/**
 * @Author: byamin
 * @Date:   2014-12-20 13:00:34
 * @Last Modified by:   byamin
 * @Last Modified time: 2014-12-31 00:48:32
 */
use \NJORM\NJTable as NJTbl;
class NJTableTest extends PHPUnit_Framework_TestCase {
  public function testTableDefine() {
    $tbl = new NJTbl('good');

    $tbl->field('field_1', 'f1')->type('int', 11, true)->notnull()->comment('这是个注释');
    $tbl->field('field_2', 'f2')->type('int', 11);
    $tbl->field('field_3', 'f3')->type('varchar', 255);
    $tbl->primaryKey('field_1', 'field_2')->autoIncrement('field_1');

    $sql = "CREATE TABLE `test_good`(
`field_1` INT(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '这是个注释',
`field_2` INT(11),
`field_3` VARCHAR(255),
PRIMARY KEY (`field_1`,`field_2`)
);";
    $this->assertEquals($sql, $tbl->toDefine());

    return $tbl;
  }

  /**
   * @depends testTableDefine
   */
  public function testTableDefine2($tbl) {

    $tbl->field('created_time', 'ct')->type('int', 11, true)->comment('创建时间');
    $tbl->field('updated_time', 'ut')->type('int', 11, true)->comment('更新时间')->default(0)->notnull();

    $sql = "CREATE TABLE `test_good`(
`field_1` INT(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '这是个注释',
`field_2` INT(11),
`field_3` VARCHAR(255),
`created_time` INT(11) unsigned COMMENT '创建时间',
`updated_time` INT(11) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
PRIMARY KEY (`field_1`,`field_2`)
);";
    $this->assertEquals($sql, $tbl->toDefine());
    return $tbl;
  }

  /**
   * @depends testTableDefine2
   */
  public function testTableSelectStar($tbl) {

    $this->assertEquals('SELECT `field_1` `f1`,`field_2` `f2`,`field_3` `f3`,`created_time` `ct`,`updated_time` `ut`', $tbl->select('*'));

    $this->assertEquals('SELECT `t1`.`field_1` `f1`,`t1`.`field_2` `f2`,`t1`.`field_3` `f3`,`t1`.`created_time` `ct`,`t1`.`updated_time` `ut`', $tbl->as('t1')->select('*'));

    $this->assertEquals('SELECT `t1`.`field_1` `f1`,`t1`.`field_2` `f2`,`t1`.`field_3` `f3`', $tbl->as('t1')->select('f1','f2','f3'));
  }
}