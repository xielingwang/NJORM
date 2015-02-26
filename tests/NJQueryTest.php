<?php
/**
 * @name: byamin
 * @Date:   2015-01-01 12:21:16
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-02-27 00:03:12
 */


use \NJORM\NJSql\NJTable;
use \NJORM\NJSql\NJExpr;
use \NJORM\NJORM;

class NJQueryTest extends PHPUnit_Framework_TestCase {

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
    $query = NJORM::pdo()->users
      ->select('name', 'pass', 'email')
      ->limit(0,2)
      ->where('uid', '>', 1)
      ->where('email', '0222')
      ->sortAsc('email');

    $this->assertEquals("SELECT `user_name` `name`,`user_pass` `pass`,`user_email` `email` FROM `qn_users` WHERE `user_id` > 1 AND `user_email` = '0222' ORDER BY `email` LIMIT 2", (string)$query);

    $model = $query->fetch();
    $this->assertEquals('gogog', $model['name']);
  }

  function testQueryNoDatasets() {
    $query = NJORM::pdo()->users
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
    $query = NJORM::pdo()->users;
    $query
    ->select('name', 'pass', 'email')
    ->limit(2)
    ->where('uid > ? AND email = ?', 1, '0222')
    ->sortAsc('email');

    $this->assertEquals('SELECT `user_name` `name`,`user_pass` `pass`,`user_email` `email` FROM `qn_users` WHERE `user_id` > ? AND `user_email` = ? ORDER BY `email` LIMIT 2', (string)$query);
    $param = $query->params();
    $this->assertTrue(in_array('0222', $param));
    $this->assertTrue(in_array(1, $param));

    $model = $query->fetch();
    $this->assertEquals('gogog', $model['name']);
  }

  function testQuerySelect3() {
    $query = NJORM::pdo()->users;
    $query
    ->select('name', 'pass', 'email')
    ->limit(2)
    ->where('balance between ? and ?', 0.4, 0.6);

    $this->assertEquals('SELECT `user_name` `name`,`user_pass` `pass`,`user_email` `email` FROM `qn_users` WHERE `user_balance` BETWEEN ? AND ? LIMIT 2', (string)$query);
    $param = $query->params();
    $this->assertTrue(in_array(0.4, $param));
    $this->assertTrue(in_array(0.6, $param));

    $model = $query->fetch();
    $this->assertEquals('gogog', $model['name']);
  }

  function testQueryInsert() {
    $query = NJORM::pdo()->users;
    $data = array(
      'name' => 'insert-name',
      'pass' => 'insert-pass',
      'balance' => floatval(rand(1000,99999)) / 100,
      'email' => 'insert-email',
      'ct' => new NJExpr('unix_timestamp()')
      );
    $this->assertEquals("INSERT INTO `qn_users`(`user_name`,`user_pass`,`user_balance`,`user_email`,`user_created`) VALUES ('insert-name','insert-pass',".$data['balance'].",'insert-email',unix_timestamp())"
      , $query->sqlInsert($data));

    $insUser = $query->insert($data);
    $this->assertGreaterThan(0, $insUser['uid'], 'inserted uid > 0');

    $query = NJORM::pdo()->users->where('uid', $insUser['uid']);
    $this->assertEquals("SELECT `user_id` `uid`,`user_name` `name`,`user_pass` `pass`,`user_balance` `balance`,`user_email` `email`,`user_created` `ct`,`user_updated` `ut` FROM `qn_users` WHERE `user_id` = '{$insUser['uid']}'", (string)$query);
    $db_user = $query->fetch();

    $this->assertNotNull($db_user, 'fetch not null after insert');
    $this->assertEquals('insert-name', $db_user['name']);
    $this->assertEquals('insert-pass', $db_user['pass']);
    $this->assertEquals('insert-email', $db_user['email']);
    $this->assertEquals($data['balance'], $db_user['balance']);
    $this->assertGreaterThan(0, $db_user['ct'], 'created > 0');
    return $db_user;
  }

  /**
   * @depends testQueryInsert
   * @return [type] [description]
   */
  function testModelSave($model_user) {
    $uid = $model_user['uid'];
    $pass = $model_user['pass'];
    $email = $model_user['email'];
/*
    $model_user['pass'] = $new_pass;
    $model_user['email'] = $new_email;
    $new_pass = 'pass'.rand(1000,9999);
    $new_email = 'email'.rand(1000,9999);

    $model_user->save();
    $this->assertTrue($model_user->saved(), 'saved');
    $this->assertEquals($model_user['pass'], $new_pass);
    $this->assertEquals($model_user['email'], $new_email);

    $fetch_model = NJORM::pdo()->users->where('uid', $uid)->fetch();
    $this->assertEquals($fetch_model['pass'], $new_pass);
    $this->assertEquals($fetch_model['email'], $new_email);

    $model_user['pass'] = $pass;
    $model_user['email'] = $email;

    $model_user->save();
    $this->assertTrue($model_user->saved(), 'saved');
    $this->assertEquals($model_user['pass'], $pass);
    $this->assertEquals($model_user['email'], $email);*/
  }

  function testQueryUpdate() {
    $query = NJORM::pdo()->users
    ->where('uid=?', 13)
    ->limit(5);
    $data = array(
      'email' => 'insert-email'.rand(100,999),
      'ut' => new NJExpr('?', $t = time()),
      );
    $this->assertEquals("UPDATE `qn_users` SET `user_email`='{$data['email']}',`user_updated`=? WHERE `user_id` = ? LIMIT 5", $query->sqlUpdate($data));
    $params = $query->paramUpdate();

    $this->assertEquals(2, count($params));
    $this->assertEquals($t, array_shift($params));
    $this->assertEquals(13, array_shift($params));

    $query->update($data);

    $model = NJORM::pdo()->users
    ->where('uid', 13)->fetch();
    $this->assertEquals($data['email'], $model['email']);
    $this->assertNotNull($model['ut'], 'updated not null');
  }

  function testQueryDelete() {
    $query = NJORM::pdo()->users;
    $query->where('email', 'abc@abc.com')
    ->limit(3,4);
    $this->assertEquals("DELETE FROM `qn_users` WHERE `user_email` = 'abc@abc.com' LIMIT 3,4", $query->sqlDelete());

    $query->delete();
  }

  function testQueryCount() {
    $query = NJORM::pdo()->users;
    $query->where('balance', 100);
    $this->assertEquals('SELECT COUNT(*) `c` FROM `qn_users` WHERE `user_balance` = 100', $query->sqlCount());
  }
}