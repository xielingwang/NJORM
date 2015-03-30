<?php
/**
 * @Author: byamin
 * @Date:   2015-02-04 23:22:54
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-30 17:19:18
 */
use NJORM\NJSql\NJTable;
use NJORM\NJModel;
use NJORM\NJCollection;
use NJORM\NJSql\NJRelationship;

class NJRelationshipTest extends PHPUnit_Framework_TestCase {
  public static function setUpBeforeClass() {
    NJTable::define('prefix_user', 'user')
    ->primary('ID', 'id')
    ->field('username', 'un')
    ->field('password', 'pwd')
    ->field('last_login', 'll')
    ->field('create_at', 'ct');

    NJTable::define('prefix_userdetail', 'detail')
    ->primary('user_id', 'uid')
    ->field('firstname', 'fn')
    ->field('lastname', 'ln')
    ->field('address', 'addr')
    ->field('wanted', 'wtd')
    ->field('birthday', 'bd');

    NJTable::define('prefix_account', 'account')
    ->primary('uid', 'uid')
    ->field('balance', 'bal');

    NJTable::define('prefix_post', 'post')
    ->primary('ID', 'pid')
    ->field('author', 'uid')
    ->field('title', 'tl')
    ->field('content', 'cnt')
    ->field('create_at', 'ct')
    ->field('id_category', 'cateid')
    ->field('modified_at', 'mt');

    NJTable::define('prefix_tag', 'tag')
    ->primary('ID', 'id')
    ->field('name', 'nm');

    NJTable::define('prefix_keyword', 'keyword')
    ->primary('ID', 'id')
    ->field('keyword', 'nm');

    NJTable::define('prefix_category', 'category')
    ->primary('ID', 'cid')
    ->field('name', 'nm');
    NJTable::define('prefix_course', 'course')
    ->primary('ID', 'id')
    ->field('name', 'nm');

    NJTable::define('prefix_post_tag', 'post_tag')
    ->primary('tag_id', 'tid')
    ->primary('post_id', 'pid');

    NJTable::define('prefix_post_keyword', 'postkeywords')
    ->primary('id_keyword', 'pkkid')
    ->primary('id_post', 'pkpid');
  }

  public function testOneOne() {

    NJRelationship::oneOne('user.id', 'detail.uid');

    $rel = NJTable::user()->rel('detail');
    $this->assertEquals(NJTable::TYPE_RELATION_ONE, $rel['type']);
    $this->assertEquals('id', $rel['sk']);
    $this->assertEquals('uid', $rel['fk']);

    $rel = NJTable::detail()->rel('user');
    $this->assertEquals(NJTable::TYPE_RELATION_ONE, $rel['type']);
    $this->assertEquals('uid', $rel['sk']);
    $this->assertEquals('id', $rel['fk']);

    NJRelationship::oneOne('user', 'account');

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

    NJRelationship::oneMany('user.id', 'post.uid');

    $rel = NJTable::user()->rel('post');
    $this->assertEquals(NJTable::TYPE_RELATION_MANY, $rel['type']);
    $this->assertEquals('id', $rel['sk']);
    $this->assertEquals('uid', $rel['fk']);

    $rel = NJTable::post()->rel('user');
    $this->assertEquals(NJTable::TYPE_RELATION_ONE, $rel['type']);
    $this->assertEquals('uid', $rel['sk']);
    $this->assertEquals('id', $rel['fk']);

    NJRelationship::oneMany('category', 'post');

    $rel = NJTable::post()->rel('category');
    $this->assertEquals(NJTable::TYPE_RELATION_ONE, $rel['type']);
    $this->assertEquals('cateid', $rel['sk']);
    $this->assertEquals('cid', $rel['fk']);

    $rel = NJTable::category()->rel('post');
    $this->assertEquals(NJTable::TYPE_RELATION_MANY, $rel['type']);
    $this->assertEquals('cid', $rel['sk']);
    $this->assertEquals('cateid', $rel['fk']);
  }

  public function testManyMany(){
    NJRelationship::manyMany('tag.id', 'post.pid', array('post_tag', 'tid', 'pid'));

    $rel = NJTable::tag()->rel('post');
    $this->assertEquals(NJTable::TYPE_RELATION_MANY_X, $rel['type']);
    $this->assertEquals('id', $rel['sk']);
    $this->assertEquals('pid', $rel['fk']);
    $this->assertEquals(['post_tag', 'tid', 'pid'], $rel['map']);

    $rel = NJTable::post()->rel('tag');
    $this->assertEquals(NJTable::TYPE_RELATION_MANY_X, $rel['type']);
    $this->assertEquals('pid', $rel['sk']);
    $this->assertEquals('id', $rel['fk']);
    $this->assertEquals(['post_tag', 'pid', 'tid'], $rel['map']);

    NJRelationship::manyMany('user', 'course');

    $rel = NJTable::user()->rel('course');
    $this->assertEquals(NJTable::TYPE_RELATION_MANY_X, $rel['type']);
    $this->assertEquals('id', $rel['sk']);
    $this->assertEquals('id', $rel['fk']);
    $this->assertEquals(['usercourse', 'uid', 'cid'], $rel['map']);

    $rel = NJTable::course()->rel('user');
    $this->assertEquals(NJTable::TYPE_RELATION_MANY_X, $rel['type']);
    $this->assertEquals('id', $rel['sk']);
    $this->assertEquals('id', $rel['fk']);
    $this->assertEquals(['usercourse', 'cid', 'uid'], $rel['map']);

    NJRelationship::manyMany('post', 'keyword', 'postkeywords');

    $rel = NJTable::post()->rel('keyword');
    $this->assertEquals(NJTable::TYPE_RELATION_MANY_X, $rel['type']);
    $this->assertEquals('pid', $rel['sk']);
    $this->assertEquals('id', $rel['fk']);
    $this->assertEquals(['postkeywords', 'pkpid', 'pkkid'], $rel['map']);

    $rel = NJTable::keyword()->rel('post');
    $this->assertEquals(NJTable::TYPE_RELATION_MANY_X, $rel['type']);
    $this->assertEquals('id', $rel['sk']);
    $this->assertEquals('pid', $rel['fk']);
    $this->assertEquals(['postkeywords', 'pkkid', 'pkpid'], $rel['map']);
  }

  public function testNJModelHasOne() {
    $user = new NJModel(NJTable::user(), array(
          'id' => 3,
          'un' => 'username',
          'pwd' => 'erewdfssreww',
          'll' => 14366555454,
          'ct' => 11366534225
          ));
    // $this->assertEquals('SELECT `user_id` `uid`,`firstname` `fn`,`lastname` `ln`,`address` `addr`,`wanted` `wtd`,`birthday` `bd` FROM `userdetail` WHERE `user_id` = 3 LIMIT 1', $user->detail->sqlSelect(), 'message');

    $detail = $user->detail('wtd', '>', 500)->select('uid,fn,ln,bd');
    // $this->assertEquals('SELECT `user_id` `uid`,`firstname` `fn`,`lastname` `ln`,`birthday` `bd` FROM `userdetail` WHERE `user_id` = 3 AND `wanted` > 500 LIMIT 1', $detail->sqlSelect(), 'message');
  }

  public function testNJModelHasMany() {
    $user = new NJModel(NJTable::user(), array(
          'id' => 3,
          'un' => 'username',
          'pwd' => 'erewdfssreww',
          'll' => 14366555454,
          'ct' => 11366534225
          ));
    // $this->assertEquals('SELECT `ID` `pid`,`author` `uid`,`title` `tl`,`content` `cnt`,`create_at` `ct`,`id_category` `cateid`,`modified_at` `mt` FROM `post` WHERE `author` = 3', $user->post->sqlSelect(), 'message');

    $posts = $user->post('mt', '>', 500)->select('uid,tl,cnt,mt');
    // $this->assertEquals('SELECT `author` `uid`,`title` `tl`,`content` `cnt`,`modified_at` `mt` FROM `post` WHERE `author` = 3 AND `modified_at` > 500', $posts->sqlSelect(), 'message');

    $post = new NJModel(NJTable::post(), array(
      'uid' => 5,
      'pid' => 56,
      'tl' => 'etetttttttttttttt',
      ));
    // $this->assertEquals('SELECT `ID` `id`,`username` `un`,`password` `pwd`,`last_login` `ll`,`create_at` `ct` FROM `user` WHERE `ID` = 5 LIMIT 1', $post->user->sqlSelect());
  }

  public function testNJCollectionHasOne() {
    $users = new NJCollection(NJTable::user(), [[
              'id' => 3,
              'un' => 'username-3',
              'pwd' => 'erewdfssreww-3',
              'll' => 14366555454,
              'ct' => 11366534225
              ],[
              'id' => 4,
              'un' => 'username-4',
              'pwd' => 'erewdfssreww-4',
              'll' => 14366555454,
              'ct' => 11366534225
              ],[
              'id' => 5,
              'un' => 'username-5',
              'pwd' => 'erewdfssreww-5',
              'll' => 14366555454,
              'ct' => 11366534225
              ]]);
    // $this->assertEquals('SELECT `user_id` `uid`,`firstname` `fn`,`lastname` `ln`,`address` `addr`,`wanted` `wtd`,`birthday` `bd` FROM `userdetail` WHERE `user_id` IN (3,4,5) LIMIT 3', $users->detail->sqlSelect(), 'message');
    $detail = $users->detail('wtd', '>', 500)->select('uid,fn,ln,bd');
    // $this->assertEquals('SELECT `user_id` `uid`,`firstname` `fn`,`lastname` `ln`,`birthday` `bd` FROM `userdetail` WHERE `user_id` IN (3,4,5) AND `wanted` > 500 LIMIT 3', $detail->sqlSelect(), 'message');
  }
}