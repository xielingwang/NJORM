<?php
/**
 * @Author: AminBy
 * @Date:   2015-03-05 15:51:47
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-07 16:47:24
 */

use \NJORM\NJSql\NJTable;
use \NJORM\NJSql\NJExpr;
use \NJORM\NJORM;
use \NJORM\NJModel;
use \NJORM\NJCollection;

class NJQueryUpdateTest extends PHPUnit_Framework_TestCase {

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

  function testQueryUpdate() {
    $query = NJORM::inst()->users
    ->where('uid=?', 13)
    ->limit(5);
    $data = array(
      'email' => 'insert-email'.rand(100,999),
      'ut' => new NJExpr('?', $t = time()),
      );
    $query->update($data);

    // assert sql and params
    extract(NJORM::lastquery(), EXTR_PREFIX_ALL, 'exec');
    $this->assertEquals("UPDATE `qn_users` SET `user_email`='{$data['email']}',`user_updated`=? WHERE `user_id` = ? LIMIT 5", $exec_sql);

    $this->assertEquals(2, count($exec_params));
    $this->assertContains($t, $exec_params);
    $this->assertContains(13, $exec_params);

    // assert data in database
    $model = NJORM::inst()->users[13];

    // assert sql and params
    extract(NJORM::lastquery(), EXTR_PREFIX_ALL, 'exec');
    $this->assertEquals("SELECT `user_id` `uid`,`user_name` `name`,`user_pass` `pass`,`user_balance` `balance`,`user_email` `email`,`user_created` `ct`,`user_updated` `ut` FROM `qn_users` WHERE `user_id` = 13 LIMIT 1", $exec_sql);
    $this->assertEquals($data['email'], $model['email']);
    $this->assertNotNull($model['ut'], 'updated not null');
  }
}