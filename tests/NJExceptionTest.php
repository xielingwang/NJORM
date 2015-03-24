<?php
/**
 * @Author: AminBy
 * @Date:   2015-03-24 17:58:29
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-24 19:31:50
 */

use NJORM\NJORM;
class NJORMTest extends PHPUnit_Framework_TestCase {

  function testException() {
    $pdo = NJORM::inst();
    try{
      $pdo->query(sprintf('SELECT * FROM noexist'));
    }
    catch(\PDOException $e) {
      echo $e->getCode();
      echo $e->getMessage();
    }
  }

  function testException() {
    $pdo = NJORM::inst();
    try{
      $pdo->query(sprintf('SELECT * FROM noexist'));
    }
    catch(\PDOException $e) {
      echo $e->getCode();
      echo $e->getMessage();
    }
  }
}