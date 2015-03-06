<?php
/**
 * @Author: byamin
 * @Date:   2015-02-14 13:21:46
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-06 14:40:26
 */

use NJORM\NJSql\NJTable;
use NJORM\NJCollection;
use NJORM\NJModel;

class NJCollectionTest extends PHPUnit_Framework_TestCase{

  public function setUp(){
    if(!NJTable::defined('qn_users')) {
      NJTable::define('qn_users', 'users')
        ->primary('user_id', 'uid')
        ->field('user_name', 'name')
        ->field('user_pass', 'pass')
        ->field('user_email', 'email');
    }
  }

  function testCollection() {

    $collection = new NJCollection(NJTable::users(), array());
    $this->assertEmpty($collection, 'is empty');
    // $this->assertTrue(empty($collection), 'is empty');

    $data = [[
      'uid' => 5,
      'name' => 'uname-5',
      'pass' => '0987445-5',
      'email' => 'gogog-5',
      ],[
      'uid' => 6,
      'name' => 'uname-6',
      'pass' => '0987445-6',
      'email' => 'gogog-6',
      ],[
      'uid' => 7,
      'name' => 'uname-7',
      'pass' => '0987445-7',
      'email' => 'gogog-7',
      ]
    ];
    $collection = new NJCollection(NJTable::users(), $data);
    $this->assertNotEmpty($collection, 'is not empty');
    $this->assertEquals(3, count($collection));

    $this->assertInstanceOf('NJORM\NJModel', $collection[1], 'element is NJModel');

    $this->assertEquals('0987445-6', $collection[1]['pass']);
    $this->assertEquals('gogog-7', $collection[2]['email']);
    $this->assertTrue($collection->saved(), 'no models in collection modified! saved() returns true');

    $collection[0]['pass'] = 'eeeeee';
    $collection[1]['email'] = 'email';
    $this->assertEquals('eeeeee', $collection[0]['pass']);
    $this->assertEquals('email', $collection[1]['email']);
    $this->assertFalse($collection->saved(), 'any models in collection modified! saved() returns false');

    $this->assertArrayHasKey(1, $collection, 'isset');
    $this->assertArrayNotHasKey(5, $collection, 'isset');
  }

  function testCollectionIterator() {
    $data = [[
      'uid' => 5,
      'name' => 'uname-5',
      'pass' => '0987445-5',
      'email' => 'gogog-5',
      ],[
      'uid' => 6,
      'name' => 'uname-6',
      'pass' => '0987445-6',
      'email' => 'gogog-6',
      ],[
      'uid' => 7,
      'name' => 'uname-7',
      'pass' => '0987445-7',
      'email' => 'gogog-7',
      ]
    ];
    $collection = new NJCollection(NJTable::users(), $data);

    foreach ($collection as $k => $model) {
      $this->assertInstanceOf('\NJORM\NJModel', $model, 'is NJModel');
      if($k == 0) {
        $this->assertEquals(5, $model['uid'], 'uid == 5');
        $this->assertEquals('uname-5', $model['name'], 'name == uname-5');
        $this->assertEquals('0987445-5', $model['pass'], 'pass == 0987445-5');
        $this->assertEquals('gogog-5', $model['email'], 'email == gogog-5');
      }
      elseif($k == 1) {
        $this->assertEquals(6, $model['uid'], 'uid == 6');
        $this->assertEquals('uname-6', $model['name'], 'name == uname-6');
        $this->assertEquals('0987445-6', $model['pass'], 'pass == 0987445-6');
        $this->assertEquals('gogog-6', $model['email'], 'email == gogog-6');
      }
      elseif($k == 2) {
        $this->assertEquals(7, $model['uid'], 'uid == 7');
        $this->assertEquals('uname-7', $model['name'], 'name == uname-7');
        $this->assertEquals('0987445-7', $model['pass'], 'pass == 0987445-7');
        $this->assertEquals('gogog-7', $model['email'], 'email == gogog-7');
      }
    }
  }
}