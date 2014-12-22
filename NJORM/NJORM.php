<?php
/**
 * @Author: Amin by
 * @Date:   2014-12-15 10:22:32
 * @Last Modified by:   byamin
 * @Last Modified time: 2014-12-21 17:10:19
 */
namespace NJORM;

class NJORM {
  function __get($table) {
    $query = NJQuery::factory();
    $query->from(NJTable::factory($table));
    return $query;
  }

  /********************************************************************************
  * PSR-0 Autoloader
  *
  * Do not use if you are using Composer to autoload dependencies.
  *******************************************************************************/

  /**
   * Slim PSR-0 autoloader
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
   * Register Slim's PSR-0 autoloader
   */
  public static function registerAutoloader()
  {
    spl_autoload_register(__NAMESPACE__ . "\\NJORM::autoload");
  }
}