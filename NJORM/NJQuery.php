<?php
/**
 * @Author: byamin
 * @Date:   2015-01-01 12:09:20
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-02-17 14:27:45
 */
namespace NJORM;
use \NJORM\NJSql;
use \NJORM\NJInterface\NJStringifiable, \Countable, \ArrayAccess;

class NJQuery implements NJStringifiable, Countable, ArrayAccess {
  const QUERY_TYPE_CREATE = 0;
  const QUERY_TYPE_SELECT = 1;
  const QUERY_TYPE_UPDATE = 2;
  const QUERY_TYPE_DELETE = 3;
  protected $_table;
  protected $_type;

  public function __construct($table) {
    if(is_string($table))
      $table = NJSql\NJTable::$table();
    $this->_table = $table;
  }

  public function stringify() {
    switch($this->_type) {
    case static::QUERY_TYPE_SELECT:
    return $this->sqlSelect();
    break;
    case static::QUERY_TYPE_CREATE:
    return $this->sqlCreate();
    break;
    case static::QUERY_TYPE_UPDATE:
    return $this->sqlUpdate();
    break;
    case static::QUERY_TYPE_DELETE:
    return $this->sqlDelete();
    break;
    }
  }
  public function __toString() {
    return $this->stringify();
  }

  public function params() {
    switch($this->_type) {
    case static::QUERY_TYPE_SELECT:
    return $this->paramSelect();
    break;
    case static::QUERY_TYPE_CREATE:
    return $this->paramCreate();
    break;
    case static::QUERY_TYPE_UPDATE:
    return $this->paramUpdate();
    break;
    case static::QUERY_TYPE_DELETE:
    return $this->paramDelete();
    break;
    }
  }

  // read
  protected $_sel_cols = array('*');
  protected $_cond_limit = null;
  protected $_cond_where = null;
  protected $_cond_sort = null;
  public function select() {
    $this->_type = static::QUERY_TYPE_SELECT;
    $tmp = array();
    foreach(func_get_args() as $arg) {
      $tmp = array_merge($tmp, explode(',', $arg));
    }
    $this->_sel_cols = array_unique($tmp);
    return $this;
  }

  public function limit() {
    $this->_cond_limit = NJSql\NJLimit::factory(func_get_args());
    return $this;
  }

  public function where($arg) {
    NJSql\NJCondition::setTable($this->_table);
    if(!($arg instanceof NJSql\NJCondition))
      $arg = NJSql\NJCondition::fact(func_get_args());
    if($this->_cond_where instanceof NJSql\NJCondition) {
      $this->_cond_where->and($arg);
    }
    else {
      $this->_cond_where = $arg;
    }
    return $this;
  }

  public function sortAsc() {
    if(is_null($this->_cond_sort))
      $this->_cond_sort = new NJSql\NJOrderby();

    foreach(func_get_args() as $field) {
      $this->_cond_sort->add($field, true);
    }
    return $this;
  }

  public function sortDesc() {
    if(is_null($this->_cond_sort))
      $this->_cond_sort = new NJSql\NJOrderby();

    foreach(func_get_args() as $field) {
      $this->_cond_sort->add($field, false);
    }
    return $this;
  }

  protected function paramSelect() {
    $parameters = array();
    if($this->_cond_where) {
      $parameters = array_merge($parameters, $this->_cond_where->parameters());
    }
    return $parameters;
  }

  protected function paramDelete() {
    $parameters = array();
    if($this->_cond_where) {
      $parameters = array_merge($parameters, $this->_cond_where->parameters());
    }
    return $parameters;    
  }

  protected function sqlSelect() {
    $sql = $this->_table->select($this->_sel_cols);
    $sql .= ' '.$this->_table->from();

    if($this->_cond_where) {
      $sql .= ' '.(string)$this->_cond_where;
    }

    if($this->_cond_sort) {
      $sql .= ' '.(string)$this->_cond_sort;
    }

    if($this->_cond_limit) {
      $sql .= ' '.(string)$this->_cond_limit;
    }
    return $sql;
  }

  public function sqlCount() {
    $sql = 'SELECT COUNT(*) ';

    if($this->_cond_where) {
      $sql .= ' '.(string)$this->_cond_where;
    }

    return $sql;
  }

  protected function getPDOParamDataType($val) {
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

  public function fetch($getMany=false) {
    $sql = $this->stringify();

    // type: prepare/execute
    if($params = $this->params()) {
      $stmt = NJORM::pdo()->prepare($sql);
      foreach($params as $k => &$p) {
        $stmt->bindParam($k+1, $p, $this->getPDOParamDataType($p));
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

    // get many
    if($getMany) {
      return $this->fetchMany($stmt);
    }

    // get one
    return $this->fetchOne($stmt);
  }

  public function fetchMany($stmt) {

  }

  public function fetchOne($stmt) {
    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
    if($result === false) {
      echo $stmt->queryString.PHP_EOL;
      echo $stmt->errorCode().PHP_EOL;
      print_r($stmt->errorInfo());
      throw new \Exception('sql execute error!');
    }
    return new NJModel($this->_table, $result);
  }

  // NJModel
  protected $_model;
  /* Countable */
  public function count() {
    // TODO
    $this->sqlCount();
    return count(array_merge($this->_data, $this->_modified));
  }

  /* ArrayAccess */
  public function offsetExists($offset) {
    if(!$this->_model) {
      $this->_model = $this->fetch();
    }
    return isset($this->_model[$offset]);
  }
  public function offsetGet($offset){
    if(!$this->_model) {
      $this->_model = $this->fetch();
    }
    return $this->_model[$offset];
  }
  public function offsetSet($offset, $value){
    if(!$this->_model) {
      $this->_model = $this->fetch();
    }
    return $this->_model[$offset] = $value;
  }
  public function offsetUnset($offset){
    if(!$this->_model) {
      $this->_model = $this->fetch();
    }
    if(isset($this->_model[$offset])){
      unset($this->_model[$offset]);
    }
  }

  // delete
  public function sqlDelete() {
    $sql = 'DELETE '.$this->_table->from();

    if($this->_cond_where) {
      $sql .= ' '.(string)$this->_cond_where;
    }

    if($this->_cond_sort) {
      $sql .= ' '.(string)$this->_cond_sort;
    }

    if($this->_cond_limit) {
      $sql .= ' '.(string)$this->_cond_limit;
    }

    return $sql;
  }

  public function delete() {
    $this->_type = static::QUERY_TYPE_DELETE;
    $sql = $this->stringify();

    // type: prepare/execute
    if($params = $this->params()) {
      $stmt = NJORM::pdo()->prepare($sql);
      foreach($params as $k => &$p) {
        $stmt->bindParam($k+1, $p, $this->getPDOParamDataType($p));
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

    return true;
  }
}