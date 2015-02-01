<?php
/**
 * @Author: byamin
 * @Date:   2015-01-08 01:18:08
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-02-02 01:26:06
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
    $this->assertTrue($v('2001:0db8:85a3:08d3:1319:8a2e:0370:7334'), '"2001:0db8:85a3:08d3:1319:8a2e:0370:7334" is good');
    $this->assertFalse($v('1.12.123.256'), '"1.12.123.256" is bad');
  }
  function testURL() {

    $v = NJValid::V('url');
    $this->assertTrue($v('http://github.com'), '"http://github.com" is good');
    $this->assertTrue($v('https://github.com'), '"https://github.com" is good');
    $this->assertTrue($v('https://gist.github.com'), '"https://gist.github.com" is good');
    $this->assertTrue($v('http://111.111.222.12'), '"http://111.111.222.12" is good');
    $this->assertTrue($v('http://111'), '"http://111" is bad');
    $this->assertFalse($v('helloworld.com'), '"helloworld.com" is bad');
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
  function testPositive() {

    $v = NJValid::V('positive');
    $this->assertTrue($v(1), '1 is positive.');
    $this->assertTrue($v("1"), '"1" is positive.');
    $this->assertTrue($v(0.1), '0.1 is positive.');
    $this->assertTrue($v("0.1"), '"0.1" is positive.');
    $this->assertFalse($v("0"), '"0" is not positive.');
    $this->assertFalse($v(0), '0 is not positive.');
    $this->assertFalse($v(-1), '-1 is not positive.');
    $this->assertFalse($v("-1"), '"-1" is not positive.');
    $this->assertFalse($v(-0.001), '-0.001 is not positive.');
  }
  function testNegative() {

    $v = NJValid::V('negative');
    $this->assertTrue($v(-1), '-1 is negative.');
    $this->assertTrue($v("-1"), '"-1" is negative.');
    $this->assertTrue($v(-0.1), '-0.1 is negative.');
    $this->assertTrue($v("-0.1"), '"-0.1" is negative.');
    $this->assertFalse($v("0"), '"0" is not negative.');
    $this->assertFalse($v(0), '0 is not negative.');
    $this->assertFalse($v(1), '1 is not negative.');
    $this->assertFalse($v("1"), '"1" is not negative.');
    $this->assertFalse($v(0.001), '0.001 is not negative.');
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

  function testStartsWithCaseSensitive() {

    $v = NJValid::V('startsWith', 'start');
    $this->assertTrue($v('startFrom'), '"startFrom" is start with "start", case sensitive');
    $this->assertFalse($v('StartFrom'), '"StartFrom" is start with "start", case sensitive');
    $this->assertFalse($v('otherFrom'), '"otherFrom" is not start with "start", case sensitive');

    $this->assertTrue($v(array('start','hello','world')), 'array start with start, case sensitive');
    $this->assertFalse($v(array('Start','hello','world')), 'array start with start, case sensitive');
    $this->assertFalse($v(array('hello','world','end')), 'array not start with start, case sensitive');
  }

  function testStartsWithCaseInsensitive() {

    $v = NJValid::V('startsWith', 'start', true);
    $this->assertTrue($v('startFrom'), '"startFrom" is start with "start", case insensitive');
    $this->assertTrue($v('StartFrom'), '"StartFrom" is start with "start", case insensitive');
    $this->assertFalse($v('otherFrom'), '"otherFrom" is not start with "start", case insensitive');

    $this->assertTrue($v(array('start','hello','world')), 'array start with start, case insensitive');
    $this->assertTrue($v(array('Start','hello','world')), 'array start with start, case sensitive');
    $this->assertFalse($v(array('hello','world','end')), 'array not start with start, case insensitive');
  }

  function testEndsWithCaseSensitive() {

    $v = NJValid::V('endsWith', 'end');
    $this->assertTrue($v('abc.end'), '"abc.end" is end with "end", case sensitive.');
    $this->assertFalse($v('abc.End'), '"abc.End" is end with "end", case sensitive.');
    $this->assertFalse($v('end.abc'), '"end.abc" is not end with "end", case sensitive.');

    $this->assertFalse($v(array('start','hello','world')), 'array end with end, case sensitive.');
    $this->assertTrue($v(array('hello','world','end')), 'array not end with end, case sensitive.');
    $this->assertFalse($v(array('hello','world','End')), 'array not end with end, case sensitive.');
  }

  function testEndsWithCaseInsensitive() {

    $v = NJValid::V('endsWith', 'end', true);
    $this->assertTrue($v('abc.end'), '"abc.end" is end with "end", case sensitive.');
    $this->assertTrue($v('abc.End'), '"abc.End" is end with "end", case sensitive.');
    $this->assertFalse($v('end.abc'), '"end.abc" is not end with "end", case sensitive.');

    $this->assertFalse($v(array('start','hello','world')), 'array end with end, case sensitive.');
    $this->assertTrue($v(array('hello','world','end')), 'array not end with end, case sensitive.');
    $this->assertTrue($v(array('hello','world','End')), 'array not end with end, case sensitive.');
  }

  function testWord() {

    $v = NJValid::V('word');
    $this->assertTrue($v('world'), '"world" is a word.');
    $this->assertTrue($v('lucky-dog'), '"lucky-dog" is a word.');
    $this->assertTrue($v('B.C.'), '"B.C." is a word.');
    $this->assertFalse($v('good morning'), '"good morning" is not a word.');
  }

  function testAlpha() {

    $v = NJValid::V('alpha');
    $this->assertTrue($v('world'), '"world" is an alpha.');
    $this->assertFalse($v('lucky-dog'), '"lucky-dog" is an alpha.');
    $this->assertFalse($v('B.C.'), '"B.C." is an alpha.');
    $this->assertFalse($v('a3a4'), '"a3a4" is an alpha.');
    $this->assertFalse($v('good morning'), '"good morning" is not an alpha.');
  }

  function testHex() {

    $v = NJValid::V('hex');
    $this->assertTrue($v('12345'), '"12345" is a hex.');
    $this->assertTrue($v('12334abcdE'), '"12334abcdE" is a hex.');
    $this->assertFalse($v('12g3e'), '"12g3e" is not a hex.');
    $this->assertFalse($v('0x6f6f6f'), '"0x6f6f6f" is not a hex.');
  }

  function testDigit() {

    $v = NJValid::V('digit');
    $this->assertTrue($v('12345'), '"12345" are all digits.');
    $this->assertFalse($v('12334abcdE'), '"12334abcdE" are not all digits.');
    $this->assertFalse($v('12.30'), '"12.30" are not all digits.');
    $this->assertFalse($v(78), 'chr(78) are not all digits.');
  }

  function testDatetime() {
    $this->markTestIncomplete();

    $v = NJValid::V('datetime');
    $this->assertTrue($v('1996-11-1'), '1996-11-1 is good.');
    // $this->assertFalse($v('1993-2-31'), '1996-2-31 is bad.');

    $this->assertTrue($v('1996-11-1 16:30'), '1996-11-1 16:30 is good.');
    $this->assertFalse($v('1996-11-1 16:61'), '1996-11-1 16:61 is bad.');

    $this->assertTrue($v('1996-11-1 16:30:59'), '1996-11-1 16:30:59 is good.');
    $this->assertFalse($v('1996-11-1 16:30:61'), '1996-11-1 16:30:61 is bad.');

    $v = NJValid::V('between', '1996-10-29', '1996-10-31');
    $this->assertTrue($v('1996-10-30'), '1996-10-30 is good.');
    $this->assertTrue($v('1996-11-1'), '1996-11-1 is bad.');
  }
}