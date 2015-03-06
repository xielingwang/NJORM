<?php
/**
 * @Author: Amin by
 * @Date:   2015-02-11 14:25:48
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-06 17:37:07
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
    $this->assertTrue($model->saved(), 'model no modified! saved() returns true');

    $model['pass'] = 'eeeeee';
    $model['email'] = 'email';
    $this->assertEquals('eeeeee', $model['pass']);
    $this->assertEquals('email', $model['email']);
    $this->assertFalse($model->saved(), 'model modified! saved() returns false');
  }

  // iterator
  function testModelIterator() {
    $data = array(
      'uid' => 5,
      'name' => 'uname',
      'pass' => '0987445',
      'email' => 'gogog',
      );
    $model = new NJModel(NJTable::users(), $data);

    $count = 0;
    foreach ($model as $key => $value) {
      $count++;
      $this->assertTrue(array_key_exists($key, $data));
      $this->assertEquals($data[$key], $value);
    }
    $this->assertEquals(4, $count);

    // after modified
    $model['pass'] = 'eeeeee';
    $model['email'] = 'email';
    $data = array(
      'uid' => 5,
      'name' => 'uname',
      'pass' => 'eeeeee',
      'email' => 'email',
      );

    $count = 0;
    foreach ($model as $key => $value) {
      $count++;
      $this->assertTrue(array_key_exists($key, $data));
      $this->assertEquals($data[$key], $value);
    }
    $this->assertEquals(4, $count);
  }

  function testModelLazyReload() {

    $model = new NJModel(NJTable::users(), array('uid' => 5, 'name' => 'good'));
    $this->assertFalse($model->isLazyReload(), 'message');

    $model = (new NJModel(NJTable::users(), array('uid' => 5)))->withLazyReload();
    $this->assertTrue($model->isLazyReload(), 'message');
  }
}