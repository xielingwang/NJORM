<?php
/**
 * @Author: byamin
 * @Date:   2014-12-27 23:54:52
 * @Last Modified by:   byamin
 * @Last Modified time: 2014-12-28 01:16:44
 */

use \NJORM\NJCom\NJField;
class NJTableTest extends PHPUnit_Framework_TestCase {
  function testTypeFormat() {
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
}