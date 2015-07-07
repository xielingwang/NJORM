<?php
/**
 * @Author: Amin by
 * @Date:   2014-12-15 10:22:32
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-06-01 15:59:06
 */
namespace NJORM;

class NJORM extends \PDO {
  static $config = array();

  public static function inst() {
    static $instName;
    static $pdo;

    if(func_num_args() > 0) {
      $name = func_get_arg(0);
      if($name != $instName) {
        $pdo = null;
        $instName = $name;
      }
    }

    if(empty($instName)) {
      $instName = 'default';
    }

    if(!$pdo) {

      try {
        if($instName == 'default') {
          $config = NJDb::getInstance()->config();
        }
        else {
          $config = NJDb::getInstance()->config($instName);
        }

        extract($config, EXTR_PREFIX_ALL, 'pdo');

        empty($pdo_options) && $pdo_options = array();

        $pdo = new static($pdo_dsn, $pdo_user, $pdo_pass, $pdo_options);
      }
      catch(\PDOException $e) {
        NJORM::error($e->getMessage());
        throw new NJException(NJException::TYPE_DBACCESS);
      }
    }
    return $pdo;
  }

  public function __construct() {
    call_user_func_array('parent::__construct', func_get_args());

    $curDriver = $this->getAttribute(\PDO::ATTR_DRIVER_NAME);
    if(static::isDriver('mysql', $curDriver) ) {
      $this->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
    }
    elseif('sqlsrv' == $curDriver) {
      $this->setAttribute(\PDO::SQLSRV_ATTR_ENCODING, \PDO::SQLSRV_ENCODING_UTF8);
    }

    $this->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
    $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
  }

  public static function isDriver($drvr1, $drvr2 = null) {
    // oci dblib mssql sqlsrv
    static $driver;
    if(!$drvr2) {
      if(!$driver) {
        $driver = static::inst()->getAttribute(\PDO::ATTR_DRIVER_NAME);
      }
      $drvr2 = $driver;
    }

    $drvrmap = array(
      'mysql' => array('mysql'),
      'mssql' => array('dblib', 'mssql', 'sqlsrv'),
    );
    $func = function($drvr) use ($drvrmap) {
      return implode('', array_unique(array_map(function($k, $v) use($drvr) {
        return in_array($drvr, $v) ? $k : null;
      }, array_keys($drvrmap), array_values($drvrmap))));
    };
    return $func($drvr1) == $func($drvr2);
  }


  /****************************************************************************************
   * Transaction
   ****************************************************************************************/
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

  /****************************************************************************************
   * prefix
   ****************************************************************************************/
  static $prefix = 'qn_';

  /****************************************************************************************
   * debug/log
   ****************************************************************************************/
  public static function lastquery($col = null) {
    if(in_array($col, array('sql', 'params')))
      return NJDb::$lastquery[$col];
    return NJDb::$lastquery;
  }

  public static function queries() {
    return NJDb::$queries;
  }

  public static function error($argument) {
    static $error;
    if(is_callable($argument)) {
      $error = $argument;
    }
    elseif(is_callable($error)) {
      $error(' [NJORM] '.$argument);
    } 
  }
  public static function debug($argument) {
    static $debug;
    if(is_callable($argument)) {
      $debug = $argument;
    }
    elseif(is_callable($debug)) {
      $debug(' [NJORM] '.$argument);
    }
  }

  /****************************************************************************************
   * new query for table
   ****************************************************************************************/
  public function __get($name) {
    return new NJQuery($name);
  }
  public function __call($name, $args) {
    if(in_array($name, array('TBegin', 'TRollback', 'TCommit'))) {
      $this->$name = true;
      return $this;
    }
    if(empty($args))
      return $this->$name;
    return call_user_func_array(array($this->$name, 'where'), $args);
  }
}