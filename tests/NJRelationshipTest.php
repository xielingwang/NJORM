<?php
/**
 * @Author: byamin
 * @Date:   2015-02-04 23:22:54
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-02-07 15:05:21
 */
use \NJORM\NJTable;
use \NJORM\NJRelationship;

class NJRelationshipTest extends PHPUnit_Framework_TestCase {
  public function setUp()
  {
    NJTable::define('user')
    ->primary('ID', 'id')
    ->field('username', 'un')
    ->field('password', 'pwd')
    ->field('last_login', 'll')
    ->field('create_at', 'ct');

    NJTable::define('userdetail')
    ->primary('uid', 'id')
    ->field('firstname', 'fn')
    ->field('lastname', 'ln')
    ->field('address', 'addr')
    ->field('birthday', 'bd');
    NJTable::define('account')
    ->primary('uid', 'id')
    ->field('balance', 'bal');

    NJTable::define('post')
    ->primary('ID', 'id')
    ->field('author', 'uid')
    ->field('title', 'tl')
    ->field('content', 'cnt')
    ->field('create_at', 'ct')
    ->field('id_category', 'cateid')
    ->field('modified_at', 'mt');

    NJTable::define('tag')
    ->primary('ID', 'id')
    ->field('name', 'nm');

    NJTable::define('category')
    ->primary('ID', 'id')
    ->field('name', 'nm');
    NJTable::define('course')
    ->primary('ID', 'id')
    ->field('name', 'nm');

    NJTable::define('post_tag')
    ->primary('tag_id', 'tid')
    ->primary('post_id', 'pid');

    NJRelationship::oneOne(array(
      'user' => null,
      'account' => null,
      ));
    NJRelationship::oneOne(array(
      'user' => 'id',
      'userdetail' => 'id',
      ));
    NJRelationship::oneMany(array(
      'post' => 'uid',
      'user' => 'id',
      ));
    NJRelationship::oneMany(array(
      'post' => null,
      'category' => null,
      ));
    NJRelationship::manyMany(
      array('tag' => 'id','post' => 'id'),
      array('tag' => 'tid','post' => 'pid'),
      'post_tag'
      );
    NJRelationship::manyMany(
      array('user' => null, 'course' => null)
      );
  }

  public function testJoin() {
    // NJTable::user()->userdetail->
  }
}