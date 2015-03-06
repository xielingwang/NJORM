<?php
/**
 * @name: byamin
 * @Date:   2015-01-01 12:21:16
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-06 20:06:17
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

  function testQueryFetchNoResult() {
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

  function testQueryFetch() {
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
    $this->assertNotNull($model, 'not null returned with records');
    $this->assertInstanceOf('NJORM\NJModel', $model, 'message');
    $this->assertTrue(strpos($model['name'], 'name') !== false);
    $this->assertGreaterThanOrEqual(2, $model['balance'], 'balance >= 2');
    $this->assertLessThanOrEqual(100, $model['balance'], 'balance <= 10');

    $model = $query->fetch();
    $this->assertNotNull($model, 'not null returned with records');
    $this->assertInstanceOf('NJORM\NJModel', $model, 'message');
    $this->assertTrue(strpos($model['name'], 'name') !== false);
    $this->assertGreaterThanOrEqual(2, $model['balance'], 'balance >= 2');
    $this->assertLessThanOrEqual(100, $model['balance'], 'balance <= 100');
  }

  function testQueryArrayAccess() {
    $users = NJORM::inst()->users;
    // $users->select('ui', 'name', 'pass', 'email', 'balance');

    // get model where id = 2
    $user2 = $users['2'];
    $this->assertInstanceOf('NJORM\NJModel', $user2, 'get by id');
    $this->assertEquals(2, $user2['uid'], 'uid == 2');

    // get model where id = 1
    $user1 = $users[1];
    $this->assertInstanceOf('NJORM\NJModel', $user1, 'get by id');
    $this->assertEquals(1, $user1['uid'], 'uid == 1');

    // get model where id = 99999
    $user99999 = $users[99999];
    $this->assertNull($user99999, 'not found whose uid is 99999');

    // get 52
    $user52 = $users[52];

    // delete 52
    unset($users[52]);
    $this->assertNull($users[52], 'message');

    return;

    // TODO: ugly api, shall we support?
    // update 52
    $users[52] = array(
      'ut' => new NJExpr('unix_timestamp()'),
      );
    $updUser = $users[52];
    $this->assertGreaterThan(1425635091, $updUser['ut'], 'update user\'s ut > 1425635091');

    // insert back 52
    $insUser = ($users[52] = array(
          'name' => $user52['name'],
          'pass' => $user52['pass'],
          'ct' => $user52['ct'],
          ));
    $insUser = $users[52];
    $this->assertEquals($insUser['uid'], $user51['uid']);
  }

  function testQueryFetchAll() {
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

  function testQueryIteratorAggregateWithRecords() {
    $query = NJORM::inst()->users;
    $query
    ->select('name,pass,email,balance')
    ->limit(5)
    ->where('balance between ? and ?', 29, 99);

    $this->assertEquals('SELECT `user_name` `name`,`user_pass` `pass`,`user_email` `email`,`user_balance` `balance` FROM `qn_users` WHERE `user_balance` BETWEEN ? AND ? LIMIT 5', (string)$query);
    $param = $query->params();
    $this->assertTrue(in_array(29, $param));
    $this->assertTrue(in_array(99, $param));

    $this->assertGreaterThan(0, $query->count(), 'with records');

    foreach($query as $model) {
      $this->assertArrayHasKey('name', $model, 'has key name');
      $this->assertArrayHasKey('pass', $model, 'has key pass');
      $this->assertArrayHasKey('email', $model, 'has key email');
      $this->assertArrayHasKey('balance', $model, 'has key balance');

      $this->assertGreaterThanOrEqual(29, $model['balance'], 'balance >= 10');
      $this->assertLessThanOrEqual(99, $model['balance'], 'balance <= 99');
    }
  }

  function testQueryIteratorAggregateWithNoRecords() {
    $query = NJORM::inst()->users;
    $query
    ->select('name,pass,email,balance')
    ->limit(5)
    ->where('balance between ? and ?', 1000, 1001);

    $this->assertEquals('SELECT `user_name` `name`,`user_pass` `pass`,`user_email` `email`,`user_balance` `balance` FROM `qn_users` WHERE `user_balance` BETWEEN ? AND ? LIMIT 5', (string)$query);
    $param = $query->params();
    $param = array_flip($param);
    $this->assertArrayHasKey(1000, $param, 'message');
    $this->assertArrayHasKey(1001, $param, 'message');

    $this->assertEquals(0, $query->count(), 'with no records');

    foreach($query as $model) {
      $this->assertTrue(false, 'it is wrong if in cirle body!');
    }
  }
}