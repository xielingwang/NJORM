<?php
/**
 * @Author: byamin
 * @Date:   2015-01-08 01:18:08
 * @Last Modified by:   Amin by
 * @Last Modified time: 2015-01-08 16:35:35
 */
use \NJORM\NJCom\NJValid;

class NJValidTest extends PHPUnit_Framework_TestCase{
  function testInteger() {
    $v = NJValid::V('integer');
    $this->assertFalse($v('eee'), 'eee not integer');
    $this->assertTrue($v(1111), '1111 is integer');
    $this->assertTrue($v(56677777755), '"1111" is not integer');
  }
  function testInCaseSensitive() {

    $v = NJValid::V('in', array(365,'12','ab', 'AQ'));
    $this->assertTrue($v('12'), '"12" soubld be in, case sensitive');
    $this->assertTrue($v('ab'), '"ab" soubld be in, case sensitive');
    $this->assertTrue($v('AQ'), '"AQ" soubld be in, case sensitive');
    $this->assertTrue($v('365'), '"365" soubld be in, case sensitive');
    $this->assertFalse($v('aQ'), '"aQ" soubld not be in, case sensitive');
    $this->assertFalse($v('PQ'), '"PQ" soubld not be in, case sensitive');
  }
  function testInCaseInsensitive() {

    $v = NJValid::V('in', array(365,'12','ab', 'AQ'), true);
    $this->assertTrue($v('12'), '"12" soubld be in, case insensive');
    $this->assertTrue($v('ab'), '"ab" soubld be in, case insensive');
    $this->assertTrue($v('AQ'), '"AQ" soubld be in, case insensive');
    $this->assertTrue($v('365'), '"365" soubld be in, case sensitive');
    $this->assertTrue($v('aQ'), '"aQ" soubld be in, case insensive');
    $this->assertFalse($v('PQ'), '"PQ" soubld not be in, case insensive');
  }
  function testTrue() {

    $v = NJValid::V('true');
    $this->assertTrue($v('on'), '"on" means true!');
    $this->assertTrue($v('1'), '"1" means true!');
    $this->assertTrue($v(1), '1 means true!');
    $this->assertTrue($v('yes'), '"yes" means true!');
    $this->assertTrue($v('true'), '"true" means true!');

    $this->assertTrue($v('On'), '"On" means true!');
    $this->assertTrue($v('1'), '"1" means true!');
    $this->assertTrue($v(1), '1 means true!');
    $this->assertTrue($v('Yes'), '"Yes" means true!');
    $this->assertTrue($v('trUe'), '"trUe" means true!');

    $this->assertFalse($v('no'), '"no" means false');
    $this->assertFalse($v('ok'), '"ok" means false');
    $this->assertFalse($v('good'), '"good" means false');

  }
  function testEmail() {
    $v = NJValid::V('email');
    $this->assertFalse($v('sseeeee11e'), '"sseeeee11e" shold not be not email');
    $this->assertTrue($v('abc-avc@qq.com'), '"abc-avc@qq.com" is an email');
  }
  function testIP() {

    $v = NJValid::V('ip');
    $this->assertTrue($v('255.255.255.255'), '"255.255.255.255" is good');
    $this->assertTrue($v('0.0.0.0'), '"0.0.0.0" is good');
    $this->assertTrue($v('1.12.123.1'), '"1.12.123.1" is good');
    $this->assertFalse($v('1.12.123.256'), '"1.12.123.256" is bad');
  }
  function testURL() {

    $v = NJValid::V('url');
    $this->assertTrue($v('http://github.com'), '"http://github.com" is good');
    $this->assertTrue($v('https://github.com'), '"https://github.com" is good');
    $this->assertTrue($v('https://gist.github.com'), '"https://gist.github.com" is good');
    $this->assertFalse($v('http://111'), '"http://111" is bad');
  }
  function testMin() {

    $v = NJValid::V('min', 20);
    $this->assertTrue($v(30), '30 >= 20 is good!');
    $this->assertTrue($v(20), '20 >= 20 is good!');
    $this->assertFalse($v(19), '19 >= 20 is bad!');
  }
  function testBetween() {

    $v = NJValid::V('between', 20, 30);
    $this->assertTrue($v(30), '30 >= 30 >= 20 is good!');
    $this->assertTrue($v(20), '30 >= 20 >= 20 is good!');
    $this->assertFalse($v(19), '30 >= 19 >= 20 is bad!');
  }
  function testContainsCaseSensitive() {

    $v = NJValid::V('contains', 'hello');
    $this->assertTrue($v('hello world'), '"hello world" contains "hello", case sensitive');
    $this->assertFalse($v('hEllo world'), '"hEllo world" do not contains "hello", case sensitive');
    $this->assertFalse($v('hi world'), '"hi world" do not contains "hello", case sensitive');
  }
  function testContainsCaseInsensitive() {

    $v = NJValid::V('contains', 'hello', true);
    $this->assertTrue($v('hello world'), '"hello world" contains "hello", case insensitive');
    $this->assertTrue($v('hEllo world'), '"hEllo world" contains "hello", case insensitive');
    $this->assertFalse($v('hi world'), '"hi world" contains "hello", case insensitive');
  }
  function testLengthBetween() {

    $v = NJValid::V('lengthBetween', 7, 16);
    $this->assertTrue($v(1234567), '1234567 strlen(7-16) is good.');
    $this->assertTrue($v('1234567'), '"1234567" strlen(7-16) is good.');
    $this->assertFalse($v("1234567890123456789"), '"1234567890123456789" strlen(7-16) is bad.');
    $this->assertFalse($v("123456"), '"123456" strlen(7-16) is bad.');
  }
}