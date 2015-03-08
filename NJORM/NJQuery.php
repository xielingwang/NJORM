<?php
/**
 * @Author: byamin
 * @Date:   2015-01-01 12:09:20
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-09 00:34:59
 */
namespace NJORM;
use \NJORM\NJSql;
use \Countable,\IteratorAggregate,\ArrayIterator, \ArrayAccess;

class NJQuery implements Countable,IteratorAggregate,ArrayAccess {
  const QUERY_TYPE_SELECT = 0;
  const QUERY_TYPE_COUNT = 1;
  const QUERY_TYPE_INSERT = 2;
  const QUERY_TYPE_UPDATE = 3;
  const QUERY_TYPE_DELETE = 4;

  protected $_table;
  protected $_type;
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
    $type = (func_num_args() <= 0)
      ? static::QUERY_TYPE_SELECT
      : intval(func_get_arg(0));

    $args = func_get_args();
    $args && array_shift($args);

    $map = array(
      static::QUERY_TYPE_SELECT => 'sqlSelect',
      static::QUERY_TYPE_COUNT => 'sqlCount',
      static::QUERY_TYPE_INSERT => 'sqlCreate',
      static::QUERY_TYPE_UPDATE => 'sqlUpdate',
      static::QUERY_TYPE_DELETE => 'sqlDelete',
      );

    return call_user_func_array(array($this, $map[$type]), $args);
  }

  public function __toString() {
    return $this->stringify();
  }

  public function params() {
    $type = (func_num_args() <= 0)
      ? static::QUERY_TYPE_SELECT
      : intval(func_get_arg(0));

    $args = func_get_args();
    $args && array_shift($args);

    $map = array(
      static::QUERY_TYPE_SELECT => 'paramsSelect',
      static::QUERY_TYPE_COUNT => 'paramsCount',
      static::QUERY_TYPE_INSERT => 'paramsCreate',
      static::QUERY_TYPE_UPDATE => 'paramsUpdate',
      static::QUERY_TYPE_DELETE => 'paramsDelete',
      );

    return call_user_func_array(array($this, $map[$type]), $args);
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


  /****************************************************************************************
   * protected param
   ****************************************************************************************/
  protected function paramsUpdate() {
    $parameters = array();
    if($this->_expr_upd) {
      $parameters = array_merge($parameters, $this->_expr_upd->parameters());
    }
    if($this->_cond_where) {
      $parameters = array_merge($parameters, $this->_cond_where->parameters());
    }
    return $parameters;
  }

  protected function paramsSelect() {
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

  protected function paramsDelete() {
    $parameters = array();
    if($this->_cond_where) {
      $parameters = array_merge($parameters, $this->_cond_where->parameters());
    }
    return $parameters;    
  }

  protected function paramsInsert(){
    $parameters = array();
    if($this->_expr_ins)
      $parameters = array_merge($parameters, $this->_expr_ins->parameters());
    return $parameters;
  }

  /****************************************************************************************
   * select/fetch api
   ****************************************************************************************/
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

  public function fetchOne() {
    return $this->_fetch()->_fetchOne($this->_last_stmt);
  }

  public function fetchAll() {
    return $this->_fetch()->_fetchMany($this->_last_stmt);
  }

  public function fetchCol($col=0, $unique=false) {
    if($this->_fetch()->_last_stmt) {
      $style = \PDO::FETCH_COLUMN;
      if($unique) $style |= \PDO::FETCH_UNIQUE;
      return $this->_last_stmt->fetchAll($style,$col);
    }
  }

  /**
   * [fetchGroupedPairs description]
   * @param  [type] $name  [description]
   * @param  [type] $value [description]
   * @return [type]        [description]
   *
   * Fetch Result Like: [k1 => [v1,v2,v3], k2 => [v4,v5], ...]
   */
  public function fetchGroupedPairs($name, $value) {
    $arr = compact('name', 'value');
    $this->_sel_cols = array_map(function($col, $alias){
      return (new NJExpr(NJMisc::wrapGraveAccent($this->_table->getField($col))))->as($alias);
    }, $arr, array_keys($arr));

    if($this->_fetch()->_last_stmt) {
      return $this->_last_stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_COLUMN);
    }
  }

  /**
   * [fetchPairs description]
   * @param  [type] $name [description]
   * @return [type]       [description]
   *
   * Case 1: fetch result like: [k1 => v1, k2 => v2 ...]
   * Case 2: fetch result lkie: [k1 => njmodel1, k2 => njmodel2...]
   * NOTE: if these are same keys, the value would be set to the last one
   */
  public function fetchPairs($name) {
    // Case 1
    if(func_num_args() > 1) {
      $value = func_get_arg(1);
      $arr = compact('name', 'value');
      $this->_sel_cols = array_map(function($col, $alias){
        return (new NJExpr(NJMisc::wrapGraveAccent($this->_table->getField($col))))->as($alias);
      }, $arr, array_keys($arr));

      if($this->_fetch()->_last_stmt) {
        return $this->_last_stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
      }
    }

    // Case 2
    else {
      $this->_sel_cols = '*';
      if($this->_fetch()->_last_stmt && $r = $this->_last_stmt->fetchAll(\PDO::FETCH_ASSOC)) {
        $ret = array();
        foreach ($r as $_) {
          $ret[$_[$name]] = new NJModel($this->_table, $_);
        }
        return $ret;
      }
    }
  }

  protected function _fetchMany($stmt) {
    if($stmt && $r = $stmt->fetchAll(\PDO::FETCH_ASSOC)) {
      return new NJCollection($this->_table, $r);
    }
  }

  protected function _fetchOne($stmt) {
    if($stmt && $r = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      return new NJModel($this->_table, $r);
    }
  }

  protected function _fetch() {
    $sql = $this->sqlSelect();
    $params = $this->params();
    $stmt_md5 = md5($sql.serialize($params));

    if(!$this->_last_stmt || $this->_last_stmt_md5 != $stmt_md5) {

      $this->_last_stmt = NJDb::execute($sql, $params);
      $this->_last_stmt_md5 = $stmt_md5;
    }
    return $this;
  }

  /****************************************************************************************
   * count and coutable
   ****************************************************************************************/
  public function count($col = '*') {
    if($this->_collection) {
      return $this->_collection->count();
    }

    $sql = $this->sqlCount($col);
    $stmt = NJDb::execute($sql, $this->params());

    if($stmt) {
      return intval($stmt->fetchColumn());
    }
    return 0;
  }

  protected function sqlCount($col) {
    $col or $col = '*';
    $col == '*' or $col = NJMisc::wrapGraveAccent($this->_table->getField($col));
    $sql = sprintf('SELECT COUNT(%s) `c` FROM %s'
      , $col
      , $this->_table->name());

    if($this->_cond_where) {
      $sql .= ' '.(string)$this->_cond_where;
    }

    return $sql;
  }


  /****************************************************************************************
   * Insertation
   ****************************************************************************************/
  protected function sqlInsert($data) {
    $this->_type = static::QUERY_TYPE_INSERT;
    $sql = 'INSERT INTO '.$this->_table->name();

    $this->_expr_ins = $this->_table->values($data);
    $sql .= $this->_expr_ins->stringify();

    return $sql;
  }

  public function insert($data) {
    $sql = $this->sqlInsert($data);

    $stmt = NJDb::execute($sql, $this->paramsInsert());

    $lastInsertId = NJORM::inst()->lastInsertId();
    if(is_numeric($lastInsertId) && ''.intval($lastInsertId) === ''.$lastInsertId) {
      $lastInsertId = floatval($lastInsertId);
    }
    return (new NJModel($this->_table, array($this->_table->primary() => $lastInsertId)))->withLazyReload();
  }

  protected function sqlUpdate($data){
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

    $stmt = NJDb::execute($sql, $this->paramsUpdate());

    $prikey = $this->_table->primary();
    return true;
  }


  /****************************************************************************************
   * Deletion
   ****************************************************************************************/
  protected function sqlDelete() {
    $this->_type = static::QUERY_TYPE_DELETE;
    $sql = 'DELETE FROM '.$this->_table->name();

    if($this->_cond_where) {
      $sql .= ' '.(string)$this->_cond_where;
    }

    if($this->_cond_sort) {
      $sql .= ' '.(string)$this->_cond_sort;
    }

    if($this->_cond_limit) {
      $sql .= ' '.$this->_cond_limit->stringify(true);
    }

    return $sql;
  }

  public function delete() {
    $sql = $this->sqlDelete();

    $stmt = NJDb::execute($sql, $this->paramsDelete());

    return true;
  }

  /****************************************************************************************
   * NJModel NJCollection
   ****************************************************************************************/
  protected $_collection;
  protected $_model;
  protected function _rewind() {
    $this->_cond_where = null;
  }

  /* IteratorAggregate */
  public function getIterator() {
    $this->_collection ? $this->_collection : $this->_collection = $this->fetchAll();
    return $this->_collection ? $this->_collection : (new ArrayIterator(array()));
  }

  /* ArrayAccess */
  // by id
  public function offsetExistsById($offset) {
    return (new NJQuery($this->_table))->where($this->_table->primary(), $offset)->count() > 0;
  }
  public function offsetGetById($offset) {
    return (new NJQuery($this->_table))->where($this->_table->primary(), $offset)->limit(1)->fetch();
  }
  public function offsetSetById($offset, $value) {
    /*
    if(is_array($value)) {
      $subquery = new NJQuery($this->_table);
      $m = $this[$offset];
      if( !$m ) {
        $value[$this->_table->primary()] = $offset;
        return $subquery->insert($value);
      }
      else {
        $m->update($value);
        return $m;
      }
    }
    */
    trigger_error('unexpected involving method of NJQuery::offsetSet()');
  }
  public function offsetUnsetById($offset) {
    (new NJQuery($this->_table))->where($this->_table->primary(), $offset)->limit(1)->delete();
  }

  // by fetching
  public function offsetExistsByFetch($offset) {
    $this->_model or $this->_model = $this->fetch();
    return $this->_model and isset($this->_model[$offset]);
  }
  public function offsetGetByFetch($offset) {
    return $this->offsetExistsByFetch($offset) ? $this->_model[$offset] : null;
  }
  public function offsetSetByFetch($offset, $value) {
    $this->_model or $this->_model = $this->fetch();
    if($this->_model) {
      $this->_model[$offset] = $value;
    }
  }
  public function offsetUnsetByFetch($offset) {
    $this->_model or $this->_model = $this->fetch();
    if($this->_model) {
      unset($this->_model[$offset]);
    }
  }

  // sumary
  public function offsetExists($offset) {
    if($this->_cond_limit or $this->_cond_where or $this->_cond_sort){
      return $this->offsetExistsByFetch($offset);
    }
    return $this->offsetExistsById($offset);
  }
  public function offsetGet($offset) {
    if($this->_cond_limit or $this->_cond_where or $this->_cond_sort){
      return $this->offsetGetByFetch($offset);
    }
    return $this->offsetGetById($offset);
  }
  public function offsetSet($offset, $value) {
    if($this->_cond_limit or $this->_cond_where or $this->_cond_sort){
      return $this->offsetSetByFetch($offset);
    }
    return $this->offsetSetById($offset);
  }
  public function offsetUnset($offset) {
    if($this->_cond_limit or $this->_cond_where or $this->_cond_sort){
      return $this->offsetUnsetByFetch($offset);
    }
    return $this->offsetUnsetById($offset);
  }
}