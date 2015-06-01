<?php
/**
 * @Author: AminBy
 * @Date:   2015-03-25 17:17:17
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-06-01 15:52:41
 */

use \NJORM\NJValid\NJDuang;

class NJDuangTest extends PHPUnit_Framework_TestCase {
  function getDuang() {
    static $duang;
    if(!$duang) {
      $duang = new NJDuang('test');

      $duang->add('integer', 'integer', 'positive');
      $duang->add('length', ['length', 9]);
      $duang->add('float', 'float', ['between', 20, 70]);
      $duang->add('lb', 'notEmpty', ['lengthBetween', 3, 5]);
      $duang->add('ne', 'notEmpty');
      $duang->add('canempty', 'integer');
      $duang->add('isin', ['in', ['1',3,5]]);
    }

    return $duang;
  }

  function testDuang() {
    $duang = $this->getDuang();

    $data = [
    'integer' => 111,
    'length' => 'abcdefghi',
    'float' => 65,
    'lb' => '1234',
    'ne' => true,
    'canempty' => '',
    'isin' => 3,
    ];

    $duang($data, false);
  }

  function testDuangException() {
    $duang = $this->getDuang();
    \NJORM\NJException::setMessage('float/max', '"{k}"不能大于{p}');
    \NJORM\NJException::setMessage('test/float/max', '测试"{k}"不能大于{p}');
    \NJORM\NJException::setMessage('length', '`{k}`长度要等于{p}');
    \NJORM\NJException::setMessage('length/length', '长度的长度要等于{p}');
    \NJORM\NJException::setMessage('test/isin', '"{k}"需要是以下值{p}');
    \NJORM\NJException::setMessage('test/notEmpty', '"{k}"不为空');

    $data = [
    'integer' => -111,
    'length' => 'abcdefgh',
    'float' => 120,
    'isin' => 'gogo'
    ];

    try {
      $duang($data, false);
    }
    catch(\NJORM\NJException $e) {
      $this->assertEquals([
        '"-111" must be a positive number',
        '长度的长度要等于9',
        '"120" must between 20 and 70',
        '"lb"不为空',
        '""\'s length must between 3 and 5',
        '"ne"不为空',
        '"isin"需要是以下值["1",3,5]',
      ], $e->getMsgs());
      return;
    }
    $this->assertFalse(true, 'opps, no exception throw!');
  }
}