<?php
/**
 * @Author: Amin by
 * @Date:   2014-12-15 10:22:32
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-02-11 01:18:40
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

  public static function pdo() {
    static $pdo;
    if(!$pdo){
      try {
        $dsn = 'mysql:dbname=test;unix_socket=/private/tmp/mysql.sock';
        $username = 'root';
        $password = 'root';
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