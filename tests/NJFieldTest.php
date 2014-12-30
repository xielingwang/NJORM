<?php
/**
 * @Author: byamin
 * @Date:   2014-12-27 23:54:52
 * @Last Modified by:   byamin
 * @Last Modified time: 2014-12-30 07:46:26
 */

use \NJORM\NJCom\NJField;
class NJFieldTest extends PHPUnit_Framework_TestCase {
  function testTypeFormat() {
    $exception = false;
    try {
      $ret = NJField::format_type('invalidtype', 256);
    }
    catch(\Exception $e) {
      $exception = true;
    }

    $ret = NJField::format_type('int');
    $this->assertEquals('INT', $ret);

    $ret = NJField::format_type('tinyint', 4, true);
    $this->assertEquals('TINYINT(4) unsigned', $ret);

    $ret = NJField::format_type('varchar', 32);
    $this->assertEquals('VARCHAR(32)', $ret);

    $exception = false;
    try {
      $ret = NJField::format_type('varchar', 256);
    }
    catch(\Exception $e) {
      $exception = true;
    }
    $this->assertTrue($exception, 'varchar 256 exception');

    $ret = NJField::format_type('decimal', 1000, 4, 20);
    $this->assertEquals('DECIMAL(1000,4)', $ret);
  }

  public function testField() {
    $field = new NJField(null);
    $field->name('field')->type('int', 10, true)->notnull()->default(111)->comment("It's a comment!");
    $this->assertEquals("`field` INT(10) unsigned NOT NULL DEFAULT 111 COMMENT 'It\'s a comment!'", (string)$field);
  }
}