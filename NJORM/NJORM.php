<?php
/**
 * @Author: Amin by
 * @Date:   2014-12-15 10:22:32
 * @Last Modified by:   Amin by
 * @Last Modified time: 2015-02-13 17:07:07
 */
namespace NJORM;

class NJORM {
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
        $dsn = sprintf("mysql:dbname=%s;host:%s", 'qndb', 'localhost');
        // $dsn = 'mysql:dbname=test;unix_socket=/private/tmp/mysql.sock';
        $username = 'root';
        $password = 'password';
        $options = array(
          1002 => 'SET NAMES utf8',
        );

        $pdo = new \PDO($dsn, $username, $password, $options);
      }
      catch(\PDOException $e) {
        die($e->getMessage());
      }
    }
    return $pdo;
  } 

  public function __get($name) {
    
  }
}