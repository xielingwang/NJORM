<?php
/**
 * @Author: byamin
 * @Date:   2015-01-01 12:21:16
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-01-03 01:12:25
 */


use \NJORM\NJCom\NJTable as NJTbl;
use \NJORM\NJQuery;

class NJQueryTest extends PHPUnit_Framework_TestCase {
  function testPrepare() {
    $tbl = new NJTbl('good');

    $tbl->field('field_1', 'f1')->type('int', 11, true)->notnull()->comment('这是个注释');
    $tbl->field('field_2', 'f2')->type('int', 11);
    $tbl->field('field_3', 'f3')->type('varchar', 255);
    $tbl->primaryKey('field_1', 'field_2')->autoIncrement('field_1');

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

  $this->assertEquals($sql, $tbl->showCreateTable());
  return $tbl;
  }

  /**
   * @depends testPrepare
   */
  function testNJQuery($tbl) {
    $query = (new NJQuery($tbl));
  }
}