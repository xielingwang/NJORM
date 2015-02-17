<?php
/**
 * @Author: byamin
 * @Date:   2015-02-17 18:36:38
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-02-17 20:33:52
 */
namespace NJORM\NJSql;
use NJORM\NJORM;

class NJDb {

  protected static function getPDOParamDataType($val) {
    if(is_null($val))
      return \PDO::PARAM_NULL;
    elseif(is_bool($val))
      return \PDO::PARAM_BOOL;
    elseif(is_int($val) || is_float($val))
      return \PDO::PARAM_INT;
    elseif(is_string($val))
      return \PDO::PARAM_STR;
    else
      return \PDO::PARAM_LOB;
  }

  public static function execute($sql, $params) {

    // type: prepare/execute
    if($params) {
      $stmt = NJORM::pdo()->prepare($sql);
      foreach($params as $k => &$p) {
        $stmt->bindParam($k+1, $p, static::getPDOParamDataType($p));
      }
      if(!$stmt->execute()) {
        echo $stmt->queryString.PHP_EOL;
        echo $stmt->errorCode().PHP_EOL;
        print_r($stmt->errorInfo());
        throw new \Exception("bindParam Error");
      }
    }

    // type: query
    else {
      $stmt = NJORM::pdo()->query($sql);
    }

    return $stmt;
  }
}