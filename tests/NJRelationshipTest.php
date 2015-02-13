<?php
/**
 * @Author: byamin
 * @Date:   2015-02-04 23:22:54
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-02-14 02:13:19
 */
use NJORM\NJSql\NJTable;
use NJORM\NJModel;
use NJORM\NJSql\NJRelationship;

class NJRelationshipTest extends PHPUnit_Framework_TestCase {
  public static function setUpBeforeClass()
  {
    NJTable::define('user')
    ->primary('ID', 'id')
    ->field('username', 'un')
    ->field('password', 'pwd')
    ->field('last_login', 'll')
    ->field('create_at', 'ct');

    NJTable::define('userdetail', 'detail')
    ->primary('user_id', 'uid')
    ->field('firstname', 'fn')
    ->field('lastname', 'ln')
    ->field('address', 'addr')
    ->field('wanted', 'wtd')
    ->field('birthday', 'bd');

    NJTable::define('account')
    ->primary('uid', 'uid')
    ->field('balance', 'bal');

    NJTable::define('post')
    ->primary('ID', 'pid')
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
    ->primary('ID', 'cid')
    ->field('name', 'nm');
    NJTable::define('course')
    ->primary('ID', 'id')
    ->field('name', 'nm');

    NJTable::define('post_tag')
    ->primary('tag_id', 'tid')
    ->primary('post_id', 'pid');
  }

  public function testOneOne() {

    NJRelationship::oneOne(array(
      'user' => 'id',
      'detail' => 'uid',
      ));

    $rel = NJTable::user()->rel('detail');
    $this->assertEquals(NJTable::TYPE_RELATION_ONE, $rel['type']);
    $this->assertEquals('id', $rel['sk']);
    $this->assertEquals('uid', $rel['fk']);

    $rel = NJTable::detail()->rel('user');
    $this->assertEquals(NJTable::TYPE_RELATION_ONE, $rel['type']);
    $this->assertEquals('uid', $rel['sk']);
    $this->assertEquals('id', $rel['fk']);

    NJRelationship::oneOne(array(
      'user' => null,
      'account' => null,
      ));

    $rel = NJTable::user()->rel('account');
    $this->assertEquals(NJTable::TYPE_RELATION_ONE, $rel['type']);
    $this->assertEquals('id', $rel['sk']);
    $this->assertEquals('uid', $rel['fk']);

    $rel = NJTable::account()->rel('user');
    $this->assertEquals(NJTable::TYPE_RELATION_ONE, $rel['type']);
    $this->assertEquals('uid', $rel['sk']);
    $this->assertEquals('id', $rel['fk']);
  }

  public function testOneMany() {

    NJRelationship::oneMany(array(
      'user' => 'id',
      'post' => 'uid',
      ));

    $rel = NJTable::user()->rel('post');
    $this->assertEquals(NJTable::TYPE_RELATION_MANY, $rel['type']);
    $this->assertEquals('id', $rel['sk']);
    $this->assertEquals('uid', $rel['fk']);

    $rel = NJTable::post()->rel('user');
    $this->assertEquals(NJTable::TYPE_RELATION_ONE, $rel['type']);
    $this->assertEquals('uid', $rel['sk']);
    $this->assertEquals('id', $rel['fk']);

    NJRelationship::oneMany(array(
      'category' => null,
      'post' => null,
      ));

    $rel = NJTable::post()->rel('category');
    $this->assertEquals(NJTable::TYPE_RELATION_ONE, $rel['type']);
    $this->assertEquals('pid', $rel['sk']);
    $this->assertEquals('cid', $rel['fk']);

    $rel = NJTable::category()->rel('post');
    $this->assertEquals(NJTable::TYPE_RELATION_MANY, $rel['type']);
    $this->assertEquals('cid', $rel['sk']);
    $this->assertEquals('cateid', $rel['fk']);
  }

  public function testManyMany(){
    NJRelationship::manyMany(
      array('tag' => 'id','post' => 'pid'),
      array('tag' => 'tid','post' => 'pid'),
      'post_tag'
      );

    $rel = NJTable::tag()->rel('post');
    $this->assertEquals(NJTable::TYPE_RELATION_MANY_X, $rel['type']);
    $this->assertEquals('id', $rel['sk']);
    $this->assertEquals('pid', $rel['fk']);
    $this->assertEquals('post_tag', $rel['map']);
    $this->assertEquals('tid', $rel['msk']);
    $this->assertEquals('pid', $rel['mfk']);

    $rel = NJTable::post()->rel('tag');
    $this->assertEquals(NJTable::TYPE_RELATION_MANY_X, $rel['type']);
    $this->assertEquals('pid', $rel['sk']);
    $this->assertEquals('id', $rel['fk']);
    $this->assertEquals('post_tag', $rel['map']);
    $this->assertEquals('pid', $rel['msk']);
    $this->assertEquals('tid', $rel['mfk']);

    NJRelationship::manyMany(
      array('user' => null, 'course' => null)
      );

    $rel = NJTable::user()->rel('course');
    $this->assertEquals(NJTable::TYPE_RELATION_MANY_X, $rel['type']);
    $this->assertEquals('id', $rel['sk']);
    $this->assertEquals('id', $rel['fk']);
    $this->assertEquals('user_course', $rel['map']);
    $this->assertEquals('id_user', $rel['msk']);
    $this->assertEquals('id_course', $rel['mfk']);

    $rel = NJTable::course()->rel('user');
    $this->assertEquals(NJTable::TYPE_RELATION_MANY_X, $rel['type']);
    $this->assertEquals('id', $rel['sk']);
    $this->assertEquals('id', $rel['fk']);
    $this->assertEquals('user_course', $rel['map']);
    $this->assertEquals('id_course', $rel['msk']);
    $this->assertEquals('id_user', $rel['mfk']);
  }

  public function testModelHasOne() {
    $user = new NJModel(NJTable::user(), array(
          'id' => 3,
          'un' => 'username',
          'pwd' => 'erewdfssreww',
          'll' => 14366555454,
          'ct' => 11366534225
          ));
    $this->assertEquals('SELECT `user_id` `uid`,`firstname` `fn`,`lastname` `ln`,`address` `addr`,`wanted` `wtd`,`birthday` `bd` FROM `userdetail` WHERE `user_id` = 3 LIMIT 1', $user->detail->sqlSelect(), 'message');

    $detail = $user->detail('wtd', '>', 500)->select('uid,fn,ln,bd');
    $this->assertEquals('SELECT `user_id` `uid`,`firstname` `fn`,`lastname` `ln`,`birthday` `bd` FROM `userdetail` WHERE `user_id` = 3 AND `wanted` > 500 LIMIT 1', $detail->sqlSelect(), 'message');
  }

  public function testModelHasMany() {
    $user = new NJModel(NJTable::user(), array(
          'id' => 3,
          'un' => 'username',
          'pwd' => 'erewdfssreww',
          'll' => 14366555454,
          'ct' => 11366534225
          ));
    $this->assertEquals('SELECT `ID` `pid`,`author` `uid`,`title` `tl`,`content` `cnt`,`create_at` `ct`,`id_category` `cateid`,`modified_at` `mt` FROM `post` WHERE `author` = 3', $user->post->sqlSelect(), 'message');

    $posts = $user->post('mt', '>', 500)->select('uid,tl,cnt,mt');
    $this->assertEquals('SELECT `author` `uid`,`title` `tl`,`content` `cnt`,`modified_at` `mt` FROM `post` WHERE `author` = 3 AND `modified_at` > 500', $posts->sqlSelect(), 'message');
  }
}