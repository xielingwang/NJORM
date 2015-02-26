<?php
/**
 * @Author: Amin by
 * @Date:   2015-02-26 14:54:17
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-02-27 00:32:39
 */
use NJORM\NJORM;
class NJORMTest extends PHPUnit_Framework_TestCase {
  function testTransaction() {
    $pdo = NJORM::inst();
    for($i = 0; $i < 10; $i++) {
      $pdo->TBegin = true;
      $pdo->query(sprintf('INSERT INTO `qn_users` (user_name,user_pass,user_email) VALUES(\'%s\',\'%s\',%d)', "name".rand(100,999), "pass".rand(1000,9999), rand(10000,99999)));
      $this->assertEquals($i, $pdo->getTransactionCounter());
    }

    for($i = 9; $i > 5; $i--) {
      $this->assertEquals($i, $pdo->getTransactionCounter());
      $pdo->TCommit = true;
    }

    $pdo->TRollback = true;
    $this->assertEquals(0, $pdo->getTransactionCounter());

    for($i = 5; $i >= 0; $i--) {
      $this->assertEquals(0, $pdo->getTransactionCounter());
      $pdo->TCommit = true;
    }
  }

  function testTransactionOK() {
    $pdo = NJORM::inst();
    for($i = 0; $i < 10; $i++) {
      $pdo->TBegin = true;
      $pdo->query(sprintf('INSERT INTO `qn_users` (user_name,user_pass,user_email) VALUES(\'%s\',\'%s\',%d)', "name-".rand(100,999), "pass-".rand(1000,9999), rand(10000,99999)));
      $this->assertEquals($i, $pdo->getTransactionCounter());
    }

    for($i = 9; $i >= 0; $i--) {
      $this->assertEquals($i, $pdo->getTransactionCounter());
      $pdo->TCommit = true;
    }

    $pdo->TRollback = true;
    $this->assertEquals(0, $pdo->getTransactionCounter());
  }
}