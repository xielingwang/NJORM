<?php
/**
 * @Author: byamin
 * @Date:   2015-01-01 12:09:20
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-06 15:15:35
 */
namespace NJORM;
use \NJORM\NJSql;
use \Countable, \ArrayAccess;

class NJQuery implements Countable, IteratorAggregate {
  const QUERY_TYPE_INSERT = 0;
  const QUERY_TYPE_SELECT = 1;
  const QUERY_TYPE_UPDATE = 2;
  const QUERY_TYPE_DELETE = 3;
  protected $_table;
  protected $_type;
  protected $_collection;
  protected $_sel_cols = '*';
  protected $_expr_sel;
  protected $_expr_ins;
  protected $_expr_upd;
  protected $_cond_limit;
  protected $_cond_where;
  protected $_cond_sort;

  public function __construct($table) {
    $this->_type = static::QUERY_TYPE_SELECT;
    if(!($table instanceof NJSql\NJTable))
      $table = NJSql\NJTable::$table();
    $this->_table = $table;
  }

  public function stringify(){
    switch($this->_type) {
    case static::QUERY_TYPE_SELECT:
    return $this->sqlSelect();
    break;
    case static::QUERY_TYPE_INSERT:
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
    case static::QUERY_TYPE_INSERT:
    return $this->paramInsert();
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
  public function selectWithout() {
    $this->_type = static::QUERY_TYPE_SELECT;
    $this->select($this->table->columnsWithout(func_get_args()));
  }
  /**
   * Case1.select('a,b,c,d', 'e,f,g'...) => a,b,c,d,e,f,g...
   * Case2.select('a,b,c,d', NJExpr, 'e,f,g', NJExpr) => a,b,c,d,expr,e,f,g,expr
   * Case3.select(NJExpr, alias) => expr as alias
   * Case4.select(NJExpr, NJExpr)
   * @return [type] [description]
   */
  public function select() {
    if(func_num_args() <= 0)
      trigger_error('NJQuery::select() expects at least 1 argument.');

    $this->_type = static::QUERY_TYPE_SELECT;
    $this->_expr_sel = null;

    $args = func_get_args();

    // NJExpr
    if(func_get_arg(0) instanceof NJSql\NJExpr) {
      if(func_num_args() > 1 && is_string(func_get_arg(1))){
        $args = array(array(func_get_arg(0), func_get_arg(1)));
      }
    }

    $this->_sel_cols = ('*' == $this->_sel_cols)
      ? $args
      : array_merge($this->_sel_cols, $args);

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
  public function paramUpdate() {
    $parameters = array();
    if($this->_expr_upd) {
      $parameters = array_merge($parameters, $this->_expr_upd->parameters());
    }
    if($this->_cond_where) {
      $parameters = array_merge($parameters, $this->_cond_where->parameters());
    }
    return $parameters;
  }

  public function paramSelect() {
    if(empty($this->_expr_sel)) {
      $this->_expr_sel = $this->_table->columns($this->_sel_cols);
    }

    $parameters = array();
    if($this->_expr_sel) {
      $parameters = array_merge($parameters, $this->_expr_sel->parameters());
    }
    if($this->_cond_where) {
      $parameters = array_merge($parameters, $this->_cond_where->parameters());
    }
    return $parameters;
  }

  public function paramDelete() {
    $parameters = array();
    if($this->_cond_where) {
      $parameters = array_merge($parameters, $this->_cond_where->parameters());
    }
    return $parameters;    
  }

  public function paramInsert(){
    $parameters = array();
    if($this->_expr_ins)
      $parameters = array_merge($parameters, $this->_expr_ins->parameters());
    return array();
  }

  protected function sqlSelect() {
    if(empty($this->_expr_sel)) {
      $this->_expr_sel = $this->_table->columns($this->_sel_cols);
    }
    $sql = sprintf('SELECT %s FROM %s'
      , $this->_expr_sel
      , $this->_table->name());

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

  protected $_last_stmt;
  protected $_last_stmt_md5;
  public function fetch() {
    $sql = $this->sqlSelect();
    $params = $this->params();
    $stmt_md5 = md5($sql.serialize($params));

    if(null === $this->_last_stmt || $this->_last_stmt_md5 != $stmt_md5) {

      $this->_last_stmt = NJDb::execute($sql, $params);
      $this->_last_stmt_md5 = $stmt_md5;
    }

    // get many
    if(func_num_args() > 0) {
      return $this->_fetchMany($this->_last_stmt);
    }

    // get one
    return $this->_fetchOne($this->_last_stmt);
  }

  public function fetchAll() {
    return $this->fetch(true);
  }

  public function fetch

  protected function _fetchMany($stmt) {
    if(!$stmt || !($r = $stmt->fetchAll(\PDO::FETCH_ASSOC))) {
      return null;
    }
    return new NJCollection($this->_table, $r);
  }

  protected function _fetchOne($stmt) {
    if(!$stmt || !($r = $stmt->fetch(\PDO::FETCH_ASSOC)) ) {
      return null;
    }
    return new NJModel($this->_table, $r);
  }

  // NJModel
  /* Countable */
  public function count() {
    if($this->_collection) {
      return $this->_collection->count();
    }

    $sql = $this->sqlCount();
    $stmt = NJDb::execute($sql, $this->params());

    if($stmt) {
      return intval($stmt->fetchColumn());
    }
    return 0;
  }

  public function sqlCount() {
    $this->_type = static::QUERY_TYPE_SELECT;
    $sql = sprintf('SELECT %s FROM %s'
      , 'COUNT(*) `c`'
      , $this->_table->name());

    if($this->_cond_where) {
      $sql .= ' '.(string)$this->_cond_where;
    }

    return $sql;
  }

  /* ArrayAccess */
  public function getIterator() {
    if($this->_collection) {
      return $this->fetchAll();
    }
    return array();
  }

  // insert
  public function sqlInsert($data) {
    $this->_type = static::QUERY_TYPE_INSERT;
    $sql = 'INSERT INTO '.$this->_table->name();

    $this->_expr_ins = $this->_table->values($data);
    $sql .= (string)$this->_expr_ins;

    return $sql;
  }

  public function insert($data) {
    $sql = $this->sqlInsert($data);

    $stmt = NJDb::execute($sql, $this->params());

    return (new NJModel($this->_table, array($this->_table->primary() => NJORM::inst()->lastInsertId())))->withLazyReload();
  }

  public function sqlUpdate($data){
    $this->_type = static::QUERY_TYPE_UPDATE;

    $this->_expr_upd = $this->_table->values($data, true);

    $sql = 'UPDATE '.$this->_table->name()
      .' SET '.(string)$this->_expr_upd;

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

  public function update($data){
    $sql = $this->sqlUpdate($data);

    $stmt = NJDb::execute($sql, $this->params());

    $prikey = $this->_table->primary();
    return true;
  }

  // delete
  public function sqlDelete() {
    $this->_type = static::QUERY_TYPE_DELETE;
    $sql = 'DELETE FROM '.$this->_table->name();

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
    $sql = $this->sqlDelete();

    $stmt = NJDb::execute($sql, $this->params());

    return true;
  }
}