<?php
/**
 * @Author: byamin
 * @Date:   2015-02-17 18:36:38
 * @Last Modified by:   Amin by
 * @Last Modified time: 2015-03-04 20:46:30
 */
namespace NJORM\NJSql;
use NJORM\NJORM;

class NJDb {

  public static function execute($sql, $params) {

    // type: prepare/execute
    if($params) {
      $stmt = NJORM::inst()->prepare($sql);

      if(!$stmt->execute($params)) {
        echo $stmt->queryString.PHP_EOL;
        echo $stmt->errorCode().PHP_EOL;
        print_r($stmt->errorInfo());
        throw new \Exception("bindParam Error");
      }
    }

    // type: query
    else {
      $stmt = NJORM::inst()->query($sql);
    }

    return $stmt;
  }
}