<?php
/**
 * @Author: AminBy
 * @Date:   2015-03-05 15:51:10
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-05 15:54:13
 */

use \NJORM\NJSql\NJTable;
use \NJORM\NJSql\NJExpr;
use \NJORM\NJORM;
use \NJORM\NJModel;
use \NJORM\NJCollection;

class NJQueryInsertTest extends PHPUnit_Framework_TestCase {

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

  function testQueryInsert() {
    $query = NJORM::inst()->users;
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

    $query = NJORM::inst()->users->where('uid', $insUser['uid']);
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

    $fetch_model = NJORM::inst()->users->where('uid', $uid)->fetch();
    $this->assertEquals($fetch_model['pass'], $new_pass);
    $this->assertEquals($fetch_model['email'], $new_email);

    $model_user['pass'] = $pass;
    $model_user['email'] = $email;

    $model_user->save();
    $this->assertTrue($model_user->saved(), 'saved');
    $this->assertEquals($model_user['pass'], $pass);
    $this->assertEquals($model_user['email'], $email);*/
  }
}