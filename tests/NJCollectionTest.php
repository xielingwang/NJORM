<?php
/**
 * @Author: byamin
 * @Date:   2015-02-14 13:21:46
 * @Last Modified by:   Amin by
 * @Last Modified time: 2015-03-04 20:21:46
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

  function testModel() {
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
}