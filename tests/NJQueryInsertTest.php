<?php
/**
 * @Author: AminBy
 * @Date:   2015-03-05 15:51:10
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-26 16:12:17
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
    $emailRand = rand(0, 999);
    $data = array(
      'name' => 'testQueryInsert'.rand(0,999),
      'pass' => 'testQueryInsert'.rand(0,999),
      'balance' => floatval(rand(1000,99999)) / 100,
      'email' => new NJExpr("CONCAT(?, UPPER(?))", 'insert-', $emailRand),
      'ct' => new NJExpr('unix_timestamp()')
      );

    $insUser = $query->insert($data);

    // assert sql and params
    extract(NJORM::lastquery(), EXTR_PREFIX_ALL, 'exec');
    $this->assertEquals("INSERT INTO `qn_users`(`user_name`,`user_pass`,`user_balance`,`user_email`,`user_created`) VALUES ('{$data['name']}','{$data['pass']}',{$data['balance']},CONCAT(?, UPPER(?)),unix_timestamp())", $exec_sql);
    $this->assertContains('insert-', $exec_params);
    $this->assertContains($emailRand, $exec_params);

    // isLazyReload() == true after inserting
    $this->assertTrue($insUser->isLazyReload(), 'is Lazy Reload after inserting');

    // data will reload when access the object isLazyReload
    $this->assertGreaterThan(0, $insUser['uid'], 'inserted uid > 0');
    extract(NJORM::lastquery(), EXTR_PREFIX_ALL, 'exec');
    $this->assertEquals("SELECT `user_id` `uid`,`user_name` `name`,`user_pass` `pass`,`user_balance` `balance`,`user_email` `email`,`user_created` `ct`,`user_updated` `ut` FROM `qn_users` WHERE `user_id` = {$insUser['uid']} LIMIT 1", $exec_sql);
    $this->assertEmpty($exec_params);

    // isLazyReload() == true after reload
    $this->assertFalse($insUser->isLazyReload(), 'not Lazy Reload get value');
    $this->assertEquals($insUser['name'], $data['name']);
    $this->assertEquals($insUser['pass'], $data['pass']);
    $this->assertEquals($insUser['email'], 'insert-'.$emailRand);
    $this->assertGreaterThan(1425635091, $insUser['ct'], 'inserted uid > 1425635091'); // 1425635091 is timestamp of 2015-03-06 17:44:51 for Asia/Shanghai Timezone


    $fetchUser = NJORM::inst()->users[$insUser['uid']];

    extract(NJORM::lastquery(), EXTR_PREFIX_ALL, 'exec');
    $this->assertEquals("SELECT `user_id` `uid`,`user_name` `name`,`user_pass` `pass`,`user_balance` `balance`,`user_email` `email`,`user_created` `ct`,`user_updated` `ut` FROM `qn_users` WHERE `user_id` = {$insUser['uid']} LIMIT 1", $exec_sql);

    // $fetchUser == $insUser
    $this->assertNotNull($fetchUser, 'fetch not null after insert');
    $this->assertEquals($insUser['name'], $fetchUser['name']);
    $this->assertEquals($insUser['pass'], $fetchUser['pass']);
    $this->assertEquals($insUser['email'], $fetchUser['email']);
    $this->assertEquals($insUser['balance'], $fetchUser['balance']);
    $this->assertEquals($insUser['ct'], $fetchUser['ct']);
  }
}