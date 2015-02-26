<?php
/**
 * @Author: Amin by
 * @Date:   2014-12-15 10:22:32
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-02-27 00:01:21
 */
namespace NJORM;
class NJORM extends \PDO {
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
  static $config = array();
  public static function config($dsn, $user, $pass, $options) {
    /**/
    $dsn = 'mysql:dbname=test;unix_socket=/private/tmp/mysql.sock';
    $username = 'root';
    $password = 'root';
    /*
    $dsn = sprintf("mysql:dbname=%s;host:%s", 'qndb', 'localhost');
    $user = 'root';
    $pass = 'password';
    /**/

    $options = array(
      \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
    );

    static::$config = compact('dsn', 'user', 'pass', 'options');
  }

  public static function pdo() {
    static $pdo;
    if(!$pdo){
      try {
        static::config(null,null,null,null);
        extract(static::$config, EXTR_PREFIX_ALL, 'pdo');
        $pdo = new NJORM($pdo_dsn, $pdo_user, $pdo_pass, $pdo_options);
      }
      catch(\PDOException $e) {
        die($e->getMessage());
      }
    }
    return $pdo;
  }

  /**
   * advansa
   * @var integer
   */
  protected $transactionCounter = 0;
  public function getTransactionCounter() {
    return $this->transactionCounter;
  }
  function beginTransaction() {
    if(!parent::inTransaction()) {
      $this->transactionCounter = 0;
      return parent::beginTransaction();
    }
    $this->transactionCounter ++;
    return parent::inTransaction();
  }

  function commit() {
    if($this->transactionCounter > 0) {
      $this->transactionCounter --;
      return true;
    }

    if(parent::inTransaction())
      return parent::commit();
  }

  function rollback() {
    $this->transactionCounter = 0;
    if(parent::inTransaction())
      return parent::rollback();
    return false;
  }

  public function __set($name, $val) {
    if(in_array($name, array('TBegin','TRollback', 'TCommit')) && $val) {
      if($name == 'TCommit')
        return $this->commit();
      if($name == 'TRollback')
        return $this->rollback();
      if($name == 'TBegin')
        return $this->beginTransaction();
    }
  }

  public function __get($name) {
    return new NJQuery($name);
  }
}