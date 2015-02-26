<?php
/**
 * @Author: Amin by
 * @Date:   2014-12-15 10:22:32
 * @Last Modified by:   Amin by
 * @Last Modified time: 2015-02-26 15:23:56
 */
namespace NJORM;

class NJORM extends \PDO {
  /**
   *
   * CREATE TABLE `qn_users` (
   * `user_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
   * `user_name` varchar(128) NOT NULL,
   * `user_pass` varchar(128) NOT NULL,
   * `user_email` bigint(128) NOT NULL,
   * PRIMARY KEY (`user_id`)
   * ) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
   * insert into qndb.qn_users (user_id, user_name, user_pass,user_email) values(1, 'aminby', '1234567', '111'), (2, 'gogog', 1111111, '222'),(3, 'ttttt', 7654321, '333');
   */
  public static function pdo() {
    static $pdo;
    if(!$pdo){
      try {
        /*
        $dsn = 'mysql:dbname=test;unix_socket=/private/tmp/mysql.sock';
        $username = 'root';
        $password = 'root';
        /**/
        $dsn = sprintf("mysql:dbname=%s;host:%s", 'qndb', 'localhost');
        $username = 'root';
        $password = 'password';
        /**/

        $pdoOpts = array(
          1002 => 'SET NAMES utf8',
        );

        $pdo = new NJORM($dsn, $username, $password, $pdoOpts);
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

  }
}