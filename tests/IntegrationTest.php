<?php
/**
 * @Author: AminBy
 * @Date:   2015-02-26 23:52:23
 * @Last Modified by:   Amin by
 * @Last Modified time: 2015-02-27 11:31:30
 */
use NJORM\NJSql\NJTable;
use NJORM\NJSql\NJExpr;
use NJORM\NJSql\NJRelationship;
use NJORM\NJORM;
class IntegrationTest extends PHPUnit_Framework_TestCase {
  /*
    DROP TABLE IF EXISTS `qn_users`;
    CREATE TABLE `qn_users` (
      `user_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      `user_name` varchar(128) NOT NULL,
      `user_pass` varchar(128) NOT NULL,
      `user_balance` float DEFAULT '0',
      `user_email` varchar(128) NOT NULL DEFAULT '',
      `user_created` int(11) unsigned DEFAULT NULL,
      `user_updated` int(11) unsigned DEFAULT NULL,
      PRIMARY KEY (`user_id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
    DROP TABLE IF EXISTS `qn_posts`;
    CREATE TABLE `qn_posts` (
     `post_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
     `post_user_id` bigint(20) unsigned NOT NULL,
     `post_title` varchar(128) NOT NULL,
     `post_content` varchar(1024) NOT NULL,
     `post_created` int(11) unsigned DEFAULT 0,
     PRIMARY KEY (`post_id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
    DROP TABLE IF EXISTS `qn_tags`;
    CREATE TABLE `qn_tags` (
     `tag_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
     `tag_name` varchar(128) NOT NULL,
     `tag_created` int(11) unsigned DEFAULT 0,
     PRIMARY KEY (`tag_id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
    DROP TABLE IF EXISTS `qn_post_tag`;
    CREATE TABLE `qn_post_tag` (
     `tag_id` bigint(20) NOT NULL,
     `post_id` bigint(20) NOT NULL,
     PRIMARY KEY (`tag_id`, `post_id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
   */
  function setUp() {
    if(!NJTable::defined('user')) {
      NJTable::define('qn_users', 'user')
        ->primary('user_id', 'id')
        ->field('user_name', 'name')
        ->field('user_pass', 'pass')
        ->field('user_balance', 'bal')
        ->field('user_email', 'eml')
        ->field('user_created', 'ct')
        ->field('user_updated', 'ut');
        NJTable::define('qn_posts', 'post')
        ->primary('post_id', 'id')
        ->field('post_user_id', 'uid')
        ->field('post_title', 'tit')
        ->field('post_content', 'cnt')
        ->field('post_created', 'ct');
        NJTable::define('qn_tags', 'tag')
        ->primary('tag_id', 'id')
        ->field('tag_name', 'nm')
        ->field('tag_created', 'ct');
        NJRelationship::oneMany(array('user'=>'id','post'=>'uid'));
        NJRelationship::manyMany(array('post'=>'id','tag'=>'id'));
    }
  }

  function testCreateUser() {
    $data = array(
      'name' => 'name-'.rand(100,999),
      'pass' => 'pass-'.rand(100,999),
      'bal' => rand(0,9999) / 100.0,
      'eml' => rand(100,999).'@gmail.com',
      'ct' => new NJExpr('unix_timestamp()'),
      // 'ct' => new NJExpr('unix_timestamp()', 60),
      'ut' => new NJExpr('unix_timestamp()'),
      );
    // echo NJORM::inst()->user->sqlInsert($data);
    // print_r(NJORM::inst()->user->paramInsert($data));
    $ins_user = NJORM::inst()->user->insert($data);
    $db_user = NJORM::inst()->user->where('id', $ins_user['id'])->limit(1)->fetch();
    $this->assertEquals($ins_user['id'], $db_user['id']);
    $this->assertEquals($ins_user['name'], $db_user['name']);
    $this->assertEquals($ins_user['pass'], $db_user['pass']);
    $this->assertEquals($ins_user['bal'], $db_user['bal']);
    $this->assertEquals($ins_user['eml'], $db_user['eml']);
    $this->assertGreaterThan(0, $db_user['ct'], 'ct > 0');
    $this->assertGreaterThan(0, $db_user['ut'], 'ut > 0');

    // $this->assertEquals(60, $db_user['ut'] - $db_user['ct']);
    $this->assertEquals(0, $db_user['ut'] - $db_user['ct']);

    return $db_user;
  }

  /**
   * @depends testCreateUser
   */
  function testCreatePost($db_user) {
    $data = array(
      'uid' => $db_user['id'],
      'tit' => 'Post-'.rand(0,999),
      'cnt' => 'Post-Content-'.rand(0,999),
      'ct' => new NJExpr('unix_timestamp()'),
      );

    var_dump($db_user);

    echo NJORM::inst()->post->sqlInsert($data);
    // print_r(NJORM::inst()->user->paramInsert($data));
    $ins_post = NJORM::inst()->post->insert($data);
    $db_post = NJORM::inst()->post->where('id = ?', $ins_post['id'])->limit(1)->fetch();
    $this->assertEquals($ins_post['uid'], $db_post['uid']);
    $this->assertEquals($ins_post['tit'], $db_post['tit']);
    $this->assertEquals($ins_post['cnt'], $db_post['cnt']);
    $this->assertGreaterThan(0, $db_post['ct'], 'ct > 0');

    $db_post1 = $db_user->post->where('id = ?', $ins_post['id'])->limit(1)->fetch();
    $this->assertEquals($db_post1['uid'], $db_post['uid']);
    $this->assertEquals($db_post1['tit'], $db_post['tit']);
    $this->assertEquals($db_post1['cnt'], $db_post['cnt']);
    $this->assertEquals($db_post1['ct'], $db_post['ct']);

    $db_post1->delete();
  }

  /**
   * @depends testCreateUser
   */
  function testDeleteUser($db_user) {
    $id = $db_user['id'];
    $db_user->delete();
    $db_user = NJORM::inst()->user->where('id', $id)->limit(1)->fetch();
    $this->assertNull($db_user, 'db user deleted');
  }
}