<?php
/**
 * @Author: Amin by
 * @Date:   2014-12-15 10:22:32
 * @Last Modified by:   Amin by
 * @Last Modified time: 2014-12-17 18:16:28
 */
namespace NJORM;

class NJDb {

  protected $dsn = 'mysql:dbname=test;unix_socket=/private/tmp/mysql.sock';
  protected $username = 'root';
  protected $password = 'root';

  protected function dbh() {
    static $dbh;
    if(is_null($dbh)) {
      $dbh = new PDO($this->dsn, $username, $password);
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
      $dbh->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
    }
    return $dbh;
  }

  function __get(string $name) {
    return new NJTable($name, $this);
  }

  function __set(string $name, NJModel $config) {
  }
}