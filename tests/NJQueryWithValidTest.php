<?php
/**
 * @Author: AminBy
 * @Date:   2015-02-17 22:21:26
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-26 19:19:08
 */

use \NJORM\NJSql\NJTable;
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

        ->field('user_balance', 'balance')
        ->valid('float', ['between', 0, 1])

        ->field('user_email', 'email')
        ->valid('email');
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
        'balance' => '0.22',
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
        'balance' => 3,
        ));
    }
    catch(\NJORM\NJException $e) {
      $msgs = [
        '"flowergogogo1"\'s length must between 5 and 9',
        'users.name should be unique, "flowergogogo1" has been existed before, extra: nil',
        '"3" must between 0 and 1',
      ];
      $this->assertEquals($msgs, $e->getMsgs());
      return;
    }

    $this->assertTrue(false, 'expects an exception here');
  }
}