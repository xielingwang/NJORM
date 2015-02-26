<?php
/**
 * @Author: AminBy
 * @Date:   2015-02-26 23:52:23
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-02-27 00:46:55
 */
use NJORM\NJSql\NJTable;
use NJORM\NJSql\NJExpr;
use NJORM\NJSql\NJRelationship;
use NJORM\NJORM;
class IntegrationTest extends PHPUnit_Framework_TestCase {
  /*
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
    CREATE TABLE `qn_posts` (
     `post_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
     `post_user_id` bigint(20) unsigned NOT NULL,
     `post_title` varchar(128) NOT NULL,
     `post_content` varchar(1024) NOT NULL,
     `post_created` int(11) unsigned DEFAULT 0,
     PRIMARY KEY (`post_id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
    CREATE TABLE `qn_tags` (
     `tag_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
     `tag_name` varchar(128) NOT NULL,
     `tag_created` int(11) unsigned DEFAULT 0,
     PRIMARY KEY (`tag_id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
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
        ->field('post_id', 'id')
        ->field('post_user_id', 'uid')
        ->field('post_title', 'tit')
        ->field('post_content', 'cnt')
        ->field('post_created', 'ct');
        NJTable::define('qn_tags', 'tag')
        ->field('tag_id', 'id')
        ->field('tag_name', 'nm')
        ->field('tag_created', 'ct');
        NJRelationship::oneMany(array('user'=>'id','post'=>'uid'));
        NJRelationship::manyMany(array('post'=>'id','tag'=>'id'));
    }
  }

  function testTest() {
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
    $this->assertEquals($ins_user['name'], $db_user['name']);
    $this->assertEquals($ins_user['pass'], $db_user['pass']);
    $this->assertEquals($ins_user['bal'], $db_user['bal']);
    $this->assertEquals($ins_user['eml'], $db_user['eml']);
    $this->assertGreaterThan(0, $db_user['ct'], 'ct > 0');
    $this->assertGreaterThan(0, $db_user['ut'], 'ut > 0');
    // $this->assertEquals(60, $db_user['ut'] - $db_user['ct']);
    $this->assertEquals(0, $db_user['ut'] - $db_user['ct']);
  }
}