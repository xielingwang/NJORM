<?php
/**
 * @name: byamin
 * @Date:   2015-01-01 12:21:16
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-27 15:46:44
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

    $model = $query->fetchOne();
    $this->assertNull($model, 'return null with no data');

    // valid sql params
    extract(NJORM::lastquery(), EXTR_PREFIX_ALL, 'exec');
    $this->assertEquals("SELECT `user_name` `name`,`user_pass` `pass`,`user_email` `email` FROM `qn_users` WHERE `user_id` > 1 AND `user_email` = 'InvalidEmail' ORDER BY `email` LIMIT 2", $exec_sql);
  }

  function testQueryFetch() {
    $query = NJORM::inst()->users;
    $query
    ->select('name', 'pass', 'email', 'balance')
    ->limit(2)
    ->where('balance between ? and ?', 2, 100);
    
    $model = $query->fetchOne();

    // sql param valdiation
    extract(NJORM::lastquery(), EXTR_PREFIX_ALL, 'exec');
    $this->assertEquals('SELECT `user_name` `name`,`user_pass` `pass`,`user_email` `email`,`user_balance` `balance` FROM `qn_users` WHERE `user_balance` BETWEEN ? AND ? LIMIT 2', $exec_sql);
    $this->assertContains(2, $exec_params);
    $this->assertContains(100, $exec_params);

    // data validation
    $this->assertNotNull($model, 'not null returned with records');
    $this->assertInstanceOf('NJORM\NJModel', $model, 'message');
    $this->assertTrue(strpos($model['name'], 'name') !== false);
    $this->assertGreaterThanOrEqual(2, $model['balance'], 'balance >= 2');
    $this->assertLessThanOrEqual(100, $model['balance'], 'balance <= 10');

    // valid sql params
    extract(NJORM::lastquery(), EXTR_PREFIX_ALL, 'exec');
    $this->assertEquals('SELECT `user_name` `name`,`user_pass` `pass`,`user_email` `email`,`user_balance` `balance` FROM `qn_users` WHERE `user_balance` BETWEEN ? AND ? LIMIT 2', $exec_sql);
    $this->assertContains(2, $exec_params);
    $this->assertContains(100, $exec_params);

    $model = $query->fetchOne();
    $this->assertNotNull($model, 'not null returned with records');
    $this->assertInstanceOf('NJORM\NJModel', $model, 'message');
    $this->assertTrue(strpos($model['name'], 'name') !== false);
    $this->assertGreaterThanOrEqual(2, $model['balance'], 'balance >= 2');
    $this->assertLessThanOrEqual(100, $model['balance'], 'balance <= 100');

    // valid sql params
    extract(NJORM::lastquery(), EXTR_PREFIX_ALL, 'exec');
    $this->assertEquals('SELECT `user_name` `name`,`user_pass` `pass`,`user_email` `email`,`user_balance` `balance` FROM `qn_users` WHERE `user_balance` BETWEEN ? AND ? LIMIT 2', $exec_sql);
    $this->assertContains(2, $exec_params);
    $this->assertContains(100, $exec_params);
  }

  function testQueryArrayAccess() {
    $users = NJORM::inst()->users;

    // get model where id = 2
    $user2 = $users['2'];
    $this->assertInstanceOf('NJORM\NJModel', $user2, 'get by id');
    $this->assertEquals(2, $user2['uid'], 'uid == 2');

    // valid sql params
    extract(NJORM::lastquery(), EXTR_PREFIX_ALL, 'exec');
    $this->assertEquals('SELECT `user_id` `uid`,`user_name` `name`,`user_pass` `pass`,`user_balance` `balance`,`user_email` `email`,`user_created` `ct`,`user_updated` `ut` FROM `qn_users` WHERE `user_id` = 2 LIMIT 1' , $exec_sql);

    // get model where id = 1
    $user1 = $users[1];
    $this->assertInstanceOf('NJORM\NJModel', $user1, 'get by id');
    $this->assertEquals(1, $user1['uid'], 'uid == 1');

    // valid sql params
    extract(NJORM::lastquery(), EXTR_PREFIX_ALL, 'exec');
    $this->assertEquals('SELECT `user_id` `uid`,`user_name` `name`,`user_pass` `pass`,`user_balance` `balance`,`user_email` `email`,`user_created` `ct`,`user_updated` `ut` FROM `qn_users` WHERE `user_id` = 1 LIMIT 1' , $exec_sql);

    // get model where id = 99999
    $user99999 = $users[99999];
    $this->assertNull($user99999, 'not found whose uid is 99999');

    // valid sql params
    extract(NJORM::lastquery(), EXTR_PREFIX_ALL, 'exec');
    $this->assertEquals('SELECT `user_id` `uid`,`user_name` `name`,`user_pass` `pass`,`user_balance` `balance`,`user_email` `email`,`user_created` `ct`,`user_updated` `ut` FROM `qn_users` WHERE `user_id` = 99999 LIMIT 1' , $exec_sql);

    $userLast = NJORM::inst()->users->sortDesc('uid')->fetchOne();

    // valid sql params
    extract(NJORM::lastquery(), EXTR_PREFIX_ALL, 'exec');
    $this->assertEquals('SELECT `user_id` `uid`,`user_name` `name`,`user_pass` `pass`,`user_balance` `balance`,`user_email` `email`,`user_created` `ct`,`user_updated` `ut` FROM `qn_users` ORDER BY `uid` DESC' , $exec_sql);

    $this->assertNotNull($userLast, 'message');
    $uid = intval($userLast['uid']);

    // get $uid
    $userSpecified = $users[$uid];

    // valid sql params
    extract(NJORM::lastquery(), EXTR_PREFIX_ALL, 'exec');
    $this->assertEquals('SELECT `user_id` `uid`,`user_name` `name`,`user_pass` `pass`,`user_balance` `balance`,`user_email` `email`,`user_created` `ct`,`user_updated` `ut` FROM `qn_users` WHERE `user_id` = '.$uid.' LIMIT 1' , $exec_sql);

    // delete $uid
    unset($users[$uid]);

    // valid sql params
    extract(NJORM::lastquery(), EXTR_PREFIX_ALL, 'exec');
    $this->assertEquals('DELETE FROM `qn_users` WHERE `user_id` = '.$uid.' LIMIT 1' , $exec_sql);

    $this->assertNull($users[$uid], 'message');

    // valid sql params
    extract(NJORM::lastquery(), EXTR_PREFIX_ALL, 'exec');
    $this->assertEquals('SELECT `user_id` `uid`,`user_name` `name`,`user_pass` `pass`,`user_balance` `balance`,`user_email` `email`,`user_created` `ct`,`user_updated` `ut` FROM `qn_users` WHERE `user_id` = '.$uid.' LIMIT 1' , $exec_sql);
  }

  function testQueryFetchAll() {
    $query = NJORM::inst()->users;
    $query
    ->select('name,pass,email,balance')
    ->limit(5)
    ->where('balance between ? and ?', 29, 99);

    $users = $query->fetchAll();

    // sql params validation
    extract(NJORM::lastquery(), EXTR_PREFIX_ALL, 'exec');
    $this->assertEquals('SELECT `user_name` `name`,`user_pass` `pass`,`user_email` `email`,`user_balance` `balance` FROM `qn_users` WHERE `user_balance` BETWEEN ? AND ? LIMIT 5' , $exec_sql);
    $this->assertContains(29, $exec_params);
    $this->assertContains(99, $exec_params);

    // data validation
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

  function testQueryFetchPairs() {
    // CASE 1
    $query = NJORM::inst()->users();
    $ret = $query->fetchPairs('uid', 'name');
    $this->assertArrayHasKey(6, $ret);
    $this->assertEquals('name-297', $ret[6]);

    // CASE 1
    $query = NJORM::inst()->users();
    $ret = $query->fetchPairs('uid');
    $this->assertArrayHasKey(6, $ret);
    $this->assertEquals('name-297', $ret[6]['name']);
    $this->assertEquals(6, $ret[6]['uid']);
  }

  function testQueryFetchGroupedPairs() {
    // CASE 1
    $query = NJORM::inst()->users();
    $ret = $query->grouped('name', 'uid');
    $this->assertArrayHasKey('name-297', $ret);
    $this->assertInternalType('array', $ret['name-297']);
    $this->assertContains(6, $ret['name-297']);
  }

  function testQuerySelectCount() {
    // Case 1
    $query = NJORM::inst()->users;
    $query->where('balance > ?', 10);
    $this->assertGreaterThan(0, $query->count(), 'num of user is greater than 20');

    // sql params validation
    extract(NJORM::lastquery(), EXTR_PREFIX_ALL, 'exec');
    $this->assertEquals('SELECT COUNT(*) `c` FROM `qn_users` WHERE `user_balance` > ?' , $exec_sql);
    $this->assertContains(10, $exec_params);

    // Case 2
    $query = NJORM::inst()->users->where('balance < -1');

    $this->assertEquals(0, $query->count('name'), 'num of user is 0');

    // sql params validation
    extract(NJORM::lastquery(), EXTR_PREFIX_ALL, 'exec');
    $this->assertEquals('SELECT COUNT(`user_name`) `c` FROM `qn_users` WHERE `user_balance` < -1' , $exec_sql);
  }

  function testQuerySelectExpr() {
    $query = NJORM::inst()->users;
    $query->select('uid')->select(NJExpr::fact('UPPER(?)', 'strtoupper'), 'up');
    $model = $query->fetchOne();

    // sql params validation
    extract(NJORM::lastquery(), EXTR_PREFIX_ALL, 'exec');
    $this->assertEquals('SELECT `user_id` `uid`,UPPER(?) `up` FROM `qn_users`' , $exec_sql);
    $this->assertContains('strtoupper', $exec_params);

    // data validtion
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
    $this->assertGreaterThan(0, $query->count(), 'with records');

    // count: sql params validation
    extract(NJORM::lastquery(), EXTR_PREFIX_ALL, 'exec');
    $this->assertEquals('SELECT COUNT(*) `c` FROM `qn_users` WHERE `user_balance` BETWEEN ? AND ?' , $exec_sql);
    $this->assertContains('29', $exec_params);
    $this->assertContains('99', $exec_params);

    // data validation
    foreach($query as $model) {
      $this->assertArrayHasKey('name', $model, 'has key name');
      $this->assertArrayHasKey('pass', $model, 'has key pass');
      $this->assertArrayHasKey('email', $model, 'has key email');
      $this->assertArrayHasKey('balance', $model, 'has key balance');

      $this->assertGreaterThanOrEqual(29, $model['balance'], 'balance >= 10');
      $this->assertLessThanOrEqual(99, $model['balance'], 'balance <= 99');
    }

    // fetchAll: sql params validation
    extract(NJORM::lastquery(), EXTR_PREFIX_ALL, 'exec');
    $this->assertEquals('SELECT `user_name` `name`,`user_pass` `pass`,`user_email` `email`,`user_balance` `balance` FROM `qn_users` WHERE `user_balance` BETWEEN ? AND ? LIMIT 5' , $exec_sql);
    $this->assertContains('29', $exec_params);
    $this->assertContains('99', $exec_params);
  }

  function testQueryIteratorAggregateWithNoRecords() {
    $query = NJORM::inst()->users;
    $query
    ->select('name,pass,email,balance')
    ->limit(5)
    ->where('balance between ? and ?', 1000, 1001);
    $this->assertEquals(0, $query->count(), 'with no records');

    // count: sql params validation
    extract(NJORM::lastquery(), EXTR_PREFIX_ALL, 'exec');
    $this->assertEquals('SELECT COUNT(*) `c` FROM `qn_users` WHERE `user_balance` BETWEEN ? AND ?' , $exec_sql);
    $this->assertContains(1000, $exec_params);
    $this->assertContains(1001, $exec_params);

    // data validation
    foreach($query as $model) {
      $this->assertTrue(false, 'it is wrong if in cirle body!');
    }

    // fetchAll: sql params validation
    extract(NJORM::lastquery(), EXTR_PREFIX_ALL, 'exec');
    $this->assertEquals('SELECT `user_name` `name`,`user_pass` `pass`,`user_email` `email`,`user_balance` `balance` FROM `qn_users` WHERE `user_balance` BETWEEN ? AND ? LIMIT 5' , $exec_sql);
    $this->assertContains(1000, $exec_params);
    $this->assertContains(1001, $exec_params);
  }
}