<?php
/**
 * @Author: Amin by
 * @Date:   2014-12-15 10:22:32
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-05 14:48:40
 */
namespace NJORM;
use \PDO;

class NJORM extends PDO {
  static $config = array();


  public static function inst() {
    static $pdo;
    if(!$pdo){
      try {
        extract(NJDb::getInstance()->config(), EXTR_PREFIX_ALL, 'pdo');
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