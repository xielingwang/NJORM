<?php
/**
 * @Author: Amin by
 * @Date:   2014-12-15 10:22:32
 * @Last Modified by:   Amin by
 * @Last Modified time: 2015-02-11 13:31:31
 */
namespace NJORM;

class NJORM {
  /********************************************************************************
  * PSR-0 Autoloader
  *
  * Do not use if you are using Composer to autoload dependencies.
  *******************************************************************************/

  /**
   * NJORM PSR-0 autoloader
   */
  public static function autoload($className)
  {
    $thisClass = str_replace(__NAMESPACE__.'\\', '', __CLASS__);

    $baseDir = __DIR__;

    if (substr($baseDir, -strlen($thisClass)) === $thisClass) {
      $baseDir = substr($baseDir, 0, -strlen($thisClass));
    }

    $className = ltrim($className, '\\');
    $fileName  = $baseDir;
    $namespace = '';
    if ($lastNsPos = strripos($className, '\\')) {
      $namespace = substr($className, 0, $lastNsPos);
      $className = substr($className, $lastNsPos + 1);
      $fileName  .= str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }
    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

    if (file_exists($fileName)) {
      require $fileName;
    }
  }

  /**
   * Register NJORM's PSR-0 autoloader
   */
  public static function registerAutoloader()
  {
    spl_autoload_register(__NAMESPACE__ . "\\NJORM::autoload");
  }

  public static function instance() {
    static $static;
    if(!$static) {
      $static = new static();
    }
    return $static;
  }

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