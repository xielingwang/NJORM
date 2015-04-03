<?php
/**
 * @Author: Amin by
 * @Date:   2014-12-15 10:22:32
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-04-02 17:26:56
 */
namespace NJORM;

class NJORM extends \PDO {
  static $config = array();

  public static function inst() {
    static $pdo;
    if(!$pdo){
      try {
        extract(NJDb::getInstance()->config(), EXTR_PREFIX_ALL, 'pdo');
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
    $this->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
    $this->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
    $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
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