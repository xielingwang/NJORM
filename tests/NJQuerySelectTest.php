<?php
/**
 * @name: byamin
 * @Date:   2015-01-01 12:21:16
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-06 01:05:07
 */


use \NJORM\NJSql\NJTable;
use \NJORM\NJSql\NJExpr;
use \NJORM\NJORM;
use \NJORM\NJModel;
use \NJORM\NJCollection;

class NJQuerySelectTest extends PHPUnit_Framework_TestCase {

  public function setUp(){
    if(!NJTable::defined('qn_users')) {
      NJTable::define('qn_users', 'users')
        ->primary('user_id', 'uid')
        ->field('user_name', 'name')
        ->field('user_pass', 'pass')
        ->field('user_balance', 'balance')
        ->field('user_email', 'email')
        ->field('user_created', 'ct')
        ->field('user_updated', 'ut')
        ;
    }
  }

  function testQuerySelect() {
    $query = NJORM::inst()->users
      ->select('name', 'pass', 'balance')
      ->limit(0,2)
      ->where('uid', '>', 1)
      ->where('balance >= ?', 49.9)
      ->sortAsc('balance');

    $this->assertEquals("SELECT `user_name` `name`,`user_pass` `pass`,`user_balance` `balance` FROM `qn_users` WHERE `user_id` > 1 AND `user_balance` >= ? ORDER BY `balance` LIMIT 2", (string)$query);

    $model = $query->fetch();

    $this->assertNotNull($model, 'return not null');
    $this->assertGreaterThanOrEqual(49.9, $model['balance'], 'message');
  }

  function testQueryNoDatasets() {
    $query = NJORM::inst()->users
      ->select('name', 'pass', 'email')
      ->limit(0,2)
      ->where('uid', '>', 1)
      ->where('email', 'InvalidEmail')
      ->sortAsc('email');

    $this->assertEquals("SELECT `user_name` `name`,`user_pass` `pass`,`user_email` `email` FROM `qn_users` WHERE `user_id` > 1 AND `user_email` = 'InvalidEmail' ORDER BY `email` LIMIT 2", (string)$query);

    $model = $query->fetch();
    $this->assertNull($model, 'return null with no data');
  }

  function testQuerySelect2() {
    $query = NJORM::inst()->users;
    $query
    ->select('name', 'pass', 'balance')
    ->limit(2)
    ->where('uid > ? AND balance >= ?', '1', 49.9)
    ->sortAsc('balance');

    $this->assertEquals('SELECT `user_name` `name`,`user_pass` `pass`,`user_balance` `balance` FROM `qn_users` WHERE `user_id` > ? AND `user_balance` >= ? ORDER BY `balance` LIMIT 2', (string)$query);
    $param = $query->params();
    $this->assertTrue(in_array(49.9, $param));
    $this->assertTrue(in_array(1, $param));

    $model = $query->fetch();
    $this->assertGreaterThanOrEqual(49.9, $model['balance'], 'balance > 49.9');
  }

  function testQuerySelect3() {
    $query = NJORM::inst()->users;
    $query
    ->select('name', 'pass', 'email', 'balance')
    ->limit(2)
    ->where('balance between ? and ?', 2, 100);

    $this->assertEquals('SELECT `user_name` `name`,`user_pass` `pass`,`user_email` `email`,`user_balance` `balance` FROM `qn_users` WHERE `user_balance` BETWEEN ? AND ? LIMIT 2', (string)$query);
    $param = $query->params();
    $this->assertTrue(in_array(2, $param));
    $this->assertTrue(in_array(100, $param));
    
    $model = $query->fetch();
    $this->assertInstanceOf('NJORM\NJModel', $model, 'message');
    $this->assertTrue(strpos($model['name'], 'name') !== false);
    $this->assertGreaterThanOrEqual(2, $model['balance'], 'balance >= 2');
    $this->assertLessThanOrEqual(100, $model['balance'], 'balance <= 10');

    $model = $query->fetch();
    $this->assertInstanceOf('NJORM\NJModel', $model, 'message');
    $this->assertTrue(strpos($model['name'], 'name') !== false);
    $this->assertGreaterThanOrEqual(2, $model['balance'], 'balance >= 2');
    $this->assertLessThanOrEqual(100, $model['balance'], 'balance <= 100');
  }

  function testQuerySelect4() {
    $query = NJORM::inst()->users;
    $query
    ->select('name,pass,email,balance')
    ->limit(5)
    ->where('balance between ? and ?', 29, 99);

    $this->assertEquals('SELECT `user_name` `name`,`user_pass` `pass`,`user_email` `email`,`user_balance` `balance` FROM `qn_users` WHERE `user_balance` BETWEEN ? AND ? LIMIT 5', (string)$query);
    $param = $query->params();
    $this->assertTrue(in_array(29, $param));
    $this->assertTrue(in_array(99, $param));

    $users = $query->fetchAll();
    $this->assertInstanceOf('NJORM\NJCollection', $users, 'message');

    foreach($users as $model) {
      $this->assertArrayHasKey('name', $model, 'has key name');
      $this->assertArrayHasKey('pass', $model, 'has key pass');
      $this->assertArrayHasKey('email', $model, 'has key email');
      $this->assertArrayHasKey('balance', $model, 'has key balance');

      $this->assertGreaterThanOrEqual(29, $model['balance'], 'balance >= 10');
      $this->assertLessThanOrEqual(99, $model['balance'], 'balance <= 99');
    }
  }

  function testQuerySelectCount() {
    $query = NJORM::inst()->users;
    $query->where('balance > ?', 10);
    $this->assertEquals('SELECT COUNT(*) `c` FROM `qn_users` WHERE `user_balance` > ?', $query->sqlCount());
    $this->assertGreaterThan(0, $query->count(), 'num of user is greater than 20');
    $query = NJORM::inst()->users;

    $query->where('balance < -1');
    $this->assertEquals('SELECT COUNT(*) `c` FROM `qn_users` WHERE `user_balance` < -1', $query->sqlCount());
    $this->assertEquals(0, $query->count(), 'num of user is 0');
  }

  function testQuerySelectExpr() {
    $query = NJORM::inst()->users;
    $query->select('uid')->select(NJExpr::fact('UPPER(?)', 'strtoupper'), 'up');
    $this->assertEquals('SELECT `user_id` `uid`,UPPER(?) `up` FROM `qn_users`', (string)$query);

    $model = $query->fetch();
    $this->assertArrayHasKey('uid', $model, 'model has key uid');
    $this->assertArrayHasKey('up', $model, 'model has key up');
    $this->assertEquals('STRTOUPPER', $model['up']);
  }
}