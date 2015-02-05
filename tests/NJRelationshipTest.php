<?php
/**
 * @Author: byamin
 * @Date:   2015-02-04 23:22:54
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-02-05 08:42:04
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

    NJTable::define('post')
    ->primary('ID', 'id')
    ->field('author', 'uid')
    ->field('title', 'tl')
    ->field('content', 'cnt')
    ->field('create_at', 'ct')
    ->field('modified_at', 'mt');

    NJTable::define('tag')
    ->primary('ID', 'id')
    ->field('name', 'nm');

    NJTable::define('post_tag')
    ->primary('tag_id', 'tid')
    ->primary('post_id', 'pid');

    NJRelationship::oneOne('user', 'userdetail', array('id' => 'id'));
    NJRelationship::oneMany('user', 'post', array('id' => 'uid'));
    NJRelationship::manyMany('tag', 'post', 'post_tag', array('id' => 'tid'), array('id' => 'pid'));
  }

  public function testJoin() {
    // NJTable::user()->userdetail->
  }
}