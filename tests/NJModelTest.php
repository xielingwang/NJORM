<?php
/**
 * @Author: Amin by
 * @Date:   2015-02-11 14:25:48
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-30 19:20:23
 */

use NJORM\NJORM;
use NJORM\NJSql\NJTable;
use NJORM\NJModel;
use NJORM\NJSql\NJRelationship;

class NJModelTest extends PHPUnit_Framework_TestCase{

  public function setUp(){
    if(!NJTable::defined('users')) {
      NJTable::define('qn_users', 'users')
        ->primary('user_id', 'id')

        ->field('user_name', 'name')
        ->valid('unique', ['lengthBetween', 7, 16])

        ->field('user_pass', 'pass')
        ->valid('unique', ['lengthBetween', 7, 16])

        ->field('user_balance', 'bal')
        ->valid('float', 'positive')

        ->field('user_email', 'eml')
        ->valid('email','unique')

        ->field('user_created', 'ct')
        ->field('user_updated', 'ut');

        NJTable::define('qn_posts', 'posts')
        ->primary('post_id', 'id')

        ->field('post_user_id', 'uid')
        ->valid('integer')

        ->field('post_title', 'tit')
        ->valid('notEmpty', ['lengthMax', 50])

        ->field('post_content', 'cnt')
        ->valid('notEmpty', ['lengthMax', 2000])

        ->field('post_created', 'ct');

        NJTable::define('qn_tags', 'tags')
        ->primary('tag_id', 'id')

        ->field('tag_name', 'nm')
        ->valid('notEmpty', ['lengthMax', 50])

        ->field('tag_created', 'ct');

        NJTable::define('qn_posts_tags', 'posttags')
        ->primary('post_id', 'pid')
        ->primary('tag_id', 'tid');

        NJRelationship::oneMany('users.id','posts.uid');
        NJRelationship::manyMany('posts.id', 'tags.id', ['posttags', 'pid','tid']);
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
    $this->assertEquals(4, count($model));

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
    $this->assertEquals(4, count($model));
  }

  function testModelLazyReload() {

    $model = new NJModel(NJTable::users(), array('uid' => 5, 'name' => 'good'));
    $this->assertFalse($model->isLazyReload(), 'message');

    $model = (new NJModel(NJTable::users(), array('uid' => 5)))->withLazyReload();
    $this->assertTrue($model->isLazyReload(), 'message');
  }

  function testRelation() {
    NJORM::error(function($str){
      echo $str;
    });
    $user8 = NJORM::inst()->users[8];
    $user8->posts->all();
    $this->assertEquals('SELECT `post_id` `id`,`post_user_id` `uid`,`post_title` `tit`,`post_content` `cnt`,`post_created` `ct` FROM `qn_posts` WHERE `post_user_id` = 8', NJORM::lastquery('sql'));

    $tm = time();
    $user8->posts->update(array('ct' => $tm));
    $this->assertEquals("UPDATE `qn_posts` SET `post_created`={$tm} WHERE `post_user_id` = 8", NJORM::lastquery('sql'));

    $post8 = NJORM::inst()->posts[8];

    $uid = $post8->user['id'];
    $this->assertEquals("SELECT `user_id` `id`,`user_name` `name`,`user_pass` `pass`,`user_balance` `bal`,`user_email` `eml`,`user_created` `ct`,`user_updated` `ut` FROM `qn_users` WHERE `user_id` = 241 LIMIT 1", NJORM::lastquery('sql'));

    $post8->tags->fetchAll();
    $this->assertEquals("SELECT `tag_id` `id`,`tag_name` `nm`,`tag_created` `ct` FROM `qn_tags` WHERE `tag_id` = (SELECT `tag_id` `tid` FROM `qn_posts_tags` WHERE `post_id` = 8)", NJORM::lastquery('sql'));
  }
}