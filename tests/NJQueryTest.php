<?php
/**
 * @name: byamin
 * @Date:   2015-01-01 12:21:16
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-02-15 00:00:53
 */


use \NJORM\NJSql\NJTable;
use \NJORM\NJQuery;

class NJQueryTest extends PHPUnit_Framework_TestCase {

  public function setUp(){
    if(!NJTable::defined('qn_users')) {
      NJTable::define('qn_users', 'users')
        ->primary('user_id', 'uid')
        ->field('user_name', 'name')
        ->field('user_pass', 'pass')
        ->field('user_balance', 'balance')
        ->field('user_email', 'email');
    }
  }

  function testNJQuery() {
    $query = new NJQuery('users');
    $query
    ->select('name', 'pass', 'email')
    ->limit(0,2)
    ->where('uid', '>', 1)
    ->where('email', '0222')
    ->sortAsc('email');

    $this->assertEquals('SELECT `user_name` `name`,`user_pass` `pass`,`user_email` `email` FROM `qn_users` WHERE `user_id` > 1 AND `user_email` = \'0222\' ORDER BY `email` LIMIT 2', (string)$query);

    $model = $query->fetch();
    $this->assertEquals('gogog', $model['name']);
  }

  function testNJQuery2() {
    $query = new NJQuery('users');
    $query
    ->select('name', 'pass', 'email')
    ->limit(2)
    ->where('`user_id` > ? AND `user_email` = ?', 1, '0222')
    ->sortAsc('email');

    $this->assertEquals('SELECT `user_name` `name`,`user_pass` `pass`,`user_email` `email` FROM `qn_users` WHERE `user_id` > ? AND `user_email` = ? ORDER BY `email` LIMIT 2', (string)$query);
    $param = $query->params();
    $this->assertTrue(in_array('0222', $param));
    $this->assertTrue(in_array(1, $param));

    $model = $query->fetch();
    $this->assertEquals('gogog', $model['name']);
  }

  function testNJQuery3() {
    $query = new NJQuery('users');
    $query
    ->select('name', 'pass', 'email')
    ->limit(2)
    ->where('`user_balance` BETWEEN ? AND ?', 0.4, 0.6);

    $this->assertEquals('SELECT `user_name` `name`,`user_pass` `pass`,`user_email` `email` FROM `qn_users` WHERE `user_balance` BETWEEN ? AND ? LIMIT 2', (string)$query);
    $param = $query->params();
    $this->assertTrue(in_array(0.4, $param));
    $this->assertTrue(in_array(0.6, $param));

    $model = $query->fetch();
    $this->assertEquals('gogog', $model['name']);
  }
}