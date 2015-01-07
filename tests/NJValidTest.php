<?php
/**
 * @Author: byamin
 * @Date:   2015-01-08 01:18:08
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-01-08 01:51:05
 */
use \NJORM\NJCom\NJValid;

class NJValidTest extends PHPUnit_Framework_TestCase{
  function testNJValid() {
    $r = NJValid::R('integer');
    $this->assertFalse($r('eee'), 'eee not integer');
    $this->assertTrue($r(1111), '1111 is integer');
    $this->assertTrue($r(56677777755), '"1111" is not integer');

    $r = NJValid::R('in', array('12','ab', 'AQ'));
    $this->assertTrue($r('12'), '"12" soubld be in');
    $this->assertTrue($r('ab'), '"ab" soubld be in');
    $this->assertTrue($r('AQ'), '"AQ" soubld be in');
    $this->assertFalse($r('PQ'), '"AQ" soubld be not in');


    $r = NJValid::R('email');
    $this->assertFalse('sseeeee11e', '"sseeeee11e" shold not be not email');
    $this->assertTrue('abc-avc@qq.com', '"abc-avc@qq.com" is an email');
  }
}