<?php
/**
 * @Author: Amin by
 * @Date:   2015-02-11 14:25:48
 * @Last Modified by:   Amin by
 * @Last Modified time: 2015-02-13 18:52:45
 */

use NJORM\NJSql\NJTable;
use NJORM\NJModel;

class NJModelTest extends PHPUnit_Framework_TestCase{

  public function setUp(){
    if(!NJTable::defined('qn_users')) {
      NJTable::define('qn_users', 'users')
        ->primary('user_id', 'uid')
        ->field('user_name', 'name')
        ->field('user_pass', 'pass')
        ->field('user_email', 'email');
    }
  }

  function testModel() {
    $data = array(
      'uid' => 5,
      'name' => 'uname',
      'pass' => '0987445',
      'email' => 'gogog',
      );
    $model = new NJModel(NJTable::users(), $data);
    $this->assertEquals('0987445', $model['pass']);
    $this->assertEquals('gogog', $model['email']);

    $model['pass'] = 'eeeeee';
    $model['email'] = 'email';
    $this->assertEquals('eeeeee', $model['pass']);
    $this->assertEquals('email', $model['email']);
  }
}