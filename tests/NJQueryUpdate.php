<?php
/**
 * @Author: AminBy
 * @Date:   2015-03-05 15:51:47
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-05 15:54:38
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
    $this->assertEquals("UPDATE `qn_users` SET `user_email`='{$data['email']}',`user_updated`=? WHERE `user_id` = ? LIMIT 5", $query->sqlUpdate($data));
    $params = $query->paramUpdate();

    $this->assertEquals(2, count($params));
    $this->assertEquals($t, array_shift($params));
    $this->assertEquals(13, array_shift($params));

    $query->update($data);

    $model = NJORM::inst()->users
    ->where('uid', 13)->fetch();
    $this->assertEquals($data['email'], $model['email']);
    $this->assertNotNull($model['ut'], 'updated not null');
  }
}