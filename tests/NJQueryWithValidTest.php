<?php
/**
 * @Author: AminBy
 * @Date:   2015-02-17 22:21:26
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-30 21:06:50
 */

use \NJORM\NJSql\NJTable;
use \NJORM\NJSql\NJExpr;
use \NJORM\NJSql\NJDefaults;
use \NJORM\NJQuery;
use \NJORM\NJORM;

class NJQueryWithValidTest extends PHPUnit_Framework_TestCase {

  public function setUp(){
    if(!NJTable::defined('qn_users')) {

      NJTable::define('qn_users', 'users')
        ->primary('user_id', 'uid')

        ->field('user_name', 'name')
        ->valid('notEmpty', ['lengthBetween', 5,9])
        ->valid('unique')

        ->field('user_pass', 'pass')
        ->valid('notEmpty', ['lengthBetween', 7,32])

        ->field('user_balance', 'bal')
        ->valid('notEmpty', 'float', ['between', 0, 200])
        ->default(100)

        ->field('user_email', 'email')
        ->valid('email')
        ->default(function(){
          return rand(100, 9999).'@useremail.com';
        })

        ->field('user_created', 'ct')
        ->default(new NJExpr('unix_timestamp()'))

        ->field('user_updated', 'ut')
        ->defaultUpd(new NJExpr('unix_timestamp()+1000'));
    }
  }

  public function testInsertDefaults() {
    NJORM::error(function($msg){
      echo $msg;
    });
    try {
      $obj = NJORM::inst()->users->insert(array(
        'name' => 'df'.rand(100, 9999999),
        'pass' => 'pwd'.rand(10000, 99999999999),
        ));
      $uid = $obj['uid'];
      $obj['bal'] = 22.3;
      $obj->save();
      $this->assertEquals('UPDATE `qn_users` SET `user_balance`=22.3,`user_updated`=unix_timestamp()+1000 WHERE `user_id` = '.$uid, NJORM::lastquery('sql'));
    }
    catch(\NJORM\NJException $e) {
      print_r($e->getMsgs());
    }
  }

  public function testInsertCheck(){
    NJORM::error(function($msg){
      echo $msg;
    });

    $query = new NJQuery('users');
    try {
      $query->insert(array(
        'name' => 'flowergogogo1',
        'pass' => '012345678910',
        'bal' => '0.22',
        'email' => 'email@email.com',
        ));
    }
    catch(\NJORM\NJException $e) {
      $this->assertEquals([
        '"flowergogogo1"\'s length must between 5 and 9',
        'users.name should be unique, "flowergogogo1" has been existed before, extra: nil'], $e->getMsgs());
      return;
    }

    $this->assertTrue(false, 'expects an exception here');
  }

  public function testUpdateCheck() {
    NJORM::error(function($msg){
      echo $msg;
    });

    $users = new NJQuery('users');
    $user = $users[127];
    try {
      $this->assertEquals('flowergogogo', $user['name']);
      $user->save(array(
        'name' => 'flowergogogo1',
        'bal' => 201,
        ));
    }
    catch(\NJORM\NJException $e) {
      $msgs = [
        '"flowergogogo1"\'s length must between 5 and 9',
        'users.name should be unique, "flowergogogo1" has been existed before, extra: nil',
        '"201" must between 0 and 200',
      ];
      $this->assertEquals($msgs, $e->getMsgs());
      return;
    }

    $this->assertTrue(false, 'expects an exception here');
  }
}