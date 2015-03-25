<?php
/**
 * @Author: AminBy
 * @Date:   2015-03-25 17:17:17
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-25 20:59:49
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
    'isin' => 3,
    ];

    $duang($data);
  }

  function testDuangException() {
    $duang = $this->getDuang();
    \NJORM\NJException::setMessage('float/max', '"{k}"不能大于{p}');
    \NJORM\NJException::setMessage('test/float/max', '测试"{k}"不能大于{p}');
    \NJORM\NJException::setMessage('length/length', '"{k}"长度要等于{p}');
    \NJORM\NJException::setMessage('test/isin', '"{k}"需要是以下值{p}');

    $data = [
    'integer' => -111,
    'length' => 'abcdefgh',
    'float' => 120,
    'isin' => 'gogo'
    ];

    try {
      $duang($data);
    }
    catch(\NJORM\NJException $e) {
      print_r($e->getMsgs());
    }

  }
}