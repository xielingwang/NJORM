<?php
/**
 * @Author: AminBy
 * @Date:   2015-02-26 23:52:23
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-27 18:01:49
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

        NJRelationship::oneMany(array('users'=>'id','posts'=>'uid'));
        NJRelationship::manyMany(array('posts'=>'id','tags'=>'id'), null, 'qn_posts_tags');
    }
  }

  function testCreateManyUser() {
    $array = [];
    $_strs = [];
    for($i=0;$i<10;$i++){
      $d = array(
        'name' => 'mname-'.rand(10000,99999),
        'pass' => 'mpass-'.rand(10000,99999),
        'bal' => rand(0,99999) / 100.0,
        'eml' => rand(0,99999).'@gmail.com',
        'ct' => new NJExpr('unix_timestamp()+?', $rand = rand(0, 3600)),
        'ut' => new NJExpr('unix_timestamp()+?', $rand + rand(0, 3600)),
        );
      $_strs[] = "('{$d['name']}','{$d['pass']}',{$d['bal']},'{$d['eml']}',unix_timestamp()+?,unix_timestamp()+?)";
      $array[] = $d;
    }
    try {
      NJORM::inst()->users->insert($array);
      $str = "INSERT INTO `qn_users`(`user_name`,`user_pass`,`user_balance`,`user_email`,`user_created`,`user_updated`) VALUES " . implode(',', $_strs);
      $this->assertEquals($str, NJORM::lastquery('sql'));
    }
    catch(\NJORM\NJException $e) {
      print_r($e->getMsgs());
      $this->assertTrue(false, 'here should no exceptions');
    }
  }

  function testCreateUser() {
    $data = array(
      'name' => 'sname-'.rand(10000,99999),
      'pass' => 'spass-'.rand(10000,99999),
      'bal' => rand(0,9999) / 100.0,
      'eml' => rand(100,9999).'@gmail.com',
      'ct' => new NJExpr('unix_timestamp()'),
      'ut' => new NJExpr('unix_timestamp()'),
      );

    $ins_user = NJORM::inst()->users->insert($data);
    $this->assertEquals("INSERT INTO `qn_users`(`user_name`,`user_pass`,`user_balance`,`user_email`,`user_created`,`user_updated`) VALUES ('{$data['name']}','{$data['pass']}',{$data['bal']},'{$data['eml']}',unix_timestamp(),unix_timestamp())", NJORM::lastquery('sql'));

    $db_user = NJORM::inst()->users[$ins_user['id']];

    $this->assertEquals("SELECT `user_id` `id`,`user_name` `name`,`user_pass` `pass`,`user_balance` `bal`,`user_email` `eml`,`user_created` `ct`,`user_updated` `ut` FROM `qn_users` WHERE `user_id` = {$ins_user['id']} LIMIT 1", NJORM::lastquery('sql'));

    $this->assertEquals($ins_user['id'], $db_user['id']);
    $this->assertEquals($ins_user['name'], $db_user['name']);
    $this->assertEquals($ins_user['pass'], $db_user['pass']);
    $this->assertEquals($ins_user['bal'], $db_user['bal']);
    $this->assertEquals($ins_user['eml'], $db_user['eml']);
    $this->assertGreaterThan(0, $db_user['ct'], 'ct > 0');
    $this->assertGreaterThan(0, $db_user['ut'], 'ut > 0');

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
      'cnt' => 'Post-Content-'.rand(0,9999),
      'ct' => new NJExpr('unix_timestamp()+?', 86400),
      );

    $ins_post = NJORM::inst()->posts->insert($data);
    $this->assertEquals("INSERT INTO `qn_posts`(`post_user_id`,`post_title`,`post_content`,`post_created`) VALUES ({$data['uid']},'{$data['tit']}','{$data['cnt']}',unix_timestamp()+?)", NJORM::lastquery('sql'));
    in_array(86400, NJORM::lastquery('params'));

    $db_post = NJORM::inst()->posts[$ins_post['id']];
    $this->assertEquals("SELECT `post_id` `id`,`post_user_id` `uid`,`post_title` `tit`,`post_content` `cnt`,`post_created` `ct` FROM `qn_posts` WHERE `post_id` = {$db_post['id']} LIMIT 1", NJORM::lastquery('sql'));

    $this->assertEquals($ins_post['uid'], $db_post['uid']);
    $this->assertEquals($ins_post['tit'], $db_post['tit']);
    $this->assertEquals($ins_post['cnt'], $db_post['cnt']);
    $this->assertGreaterThan(0, $db_post['ct'], 'ct > 0');

    $db_user->posts->delete($ins_post['id']);
    $this->assertEquals("DELETE FROM `qn_posts` WHERE `post_id` = {$ins_post['id']}", NJORM::lastquery('sql'));

    $db_post->delete();
    $this->assertEquals("DELETE FROM `qn_posts` WHERE `post_id` = {$ins_post['id']}", NJORM::lastquery('sql'));
  }

  /**
   * @depends testCreateUser
   */
  function testCreatePost2($db_user) {
    $data = array(
      'tit' => 'Post-'.rand(0,99999),
      'cnt' => 'Post-Content-'.rand(0,9999),
      'ct' => new NJExpr('unix_timestamp()+?', 86400),
      );

    $ins_post = $db_user->posts->insert($data);
    $this->assertEquals("INSERT INTO `qn_posts`(`post_user_id`,`post_title`,`post_content`,`post_created`) VALUES ({$db_user['id']},'{$data['tit']}','{$data['cnt']}',unix_timestamp()+?)", NJORM::lastquery('sql'));
    in_array(86400, NJORM::lastquery('params'));

    $id = $ins_post['id'];
    $ins_post->delete();
    $this->assertEquals("DELETE FROM `qn_posts` WHERE `post_id` = {$id}", NJORM::lastquery('sql'));
  }

  /**
   * @depends testCreateUser
   */
  function testCreateTags($db_user) {
    $data = array(
      'tit' => 'Post-'.rand(0,99999),
      'cnt' => 'Post-Content-'.rand(0,9999),
      'ct' => new NJExpr('unix_timestamp()+?', 86400),
      );
    $db_post = $db_user->posts->insert($data);
    $this->assertEquals("INSERT INTO `qn_posts`(`post_user_id`,`post_title`,`post_content`,`post_created`) VALUES ({$db_user['id']},'{$data['tit']}','{$data['cnt']}',unix_timestamp()+?)", NJORM::lastquery('sql'));
    in_array(86400, NJORM::lastquery('params'));

    print_r($db_post->_table->_has);

    $db_post->delete();
  }

  /**
   * @depends testCreateUser
   * @depends testCreatePost
   */
  function testDeleteUser($db_user) {
    $id = $db_user['id'];
    NJORM::inst()->users[$id]->delete();
    $db_user = NJORM::inst()->users[$id];
    $this->assertNull($db_user, 'db user deleted');
  }
}