<?php
/**
 * NJQuery.php
 * 
 * @Author: AminBy (xielingwang@gmail.com)
 * @Date:   2015-04-03 23:36:06
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-05-04 20:47:34
 */
namespace NJORM;
use \NJORM\NJSql;
use \NJORM\NJSql\NJExprInterface;
use \Countable,\IteratorAggregate,\ArrayIterator, \ArrayAccess, \JsonSerializable;

class NJQuery implements Countable,IteratorAggregate,ArrayAccess,NJExprInterface,JsonSerializable {
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
  protected $_rel_data; // relationship data

  public function __construct($table) {
    $this->_type = static::QUERY_TYPE_SELECT;
    if(!($table instanceof NJSql\NJTable))
      $table = NJSql\NJTable::$table();
    $this->_table = $table;
  }

  public function setRelData($data) {
    $this->_rel_data = $data;
    return $this;
  }

  /************************************************
   * NJExprInterface
   ***********************************************/
  public function stringify(){
    return $this->sqlSelect();
  }

  public function parameters() {
    return $this->paramsSelect();
  }
  public function isEnclosed() {
    return true;
  }

  /************************************************
   * select
   * limit
   * where
   * sortAsc, sortDesc
   ***********************************************/
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
      $this->_cond_sort = new NJSql\NJOrderBy();

    foreach(func_get_args() as $field) {
      $this->_cond_sort->add($field, true);
    }
    return $this;
  }

  public function sortDesc() {
    if(is_null($this->_cond_sort))
      $this->_cond_sort = new NJSql\NJOrderBy();

    foreach(func_get_args() as $field) {
      $this->_cond_sort->add($field, false);
    }
    return $this;
  }

  /****************************************************************************************
   * protected params
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
    if($this->_rel_data) {
      $rd =& $this->_rel_data;

      if(in_array($rd['rel']['type'], array(NJSql\NJTable::TYPE_RELATION_ONE, NJSql\NJTable::TYPE_RELATION_MANY))) {
        $this->where($rd['rel']['fk'], $rd['data']);

        if($rd['rel']['type'] == NJSql\NJTable::TYPE_RELATION_ONE && !is_array($rd['data'])) {
          $this->limit(1);
        }
      }
      else {
        list($mt, $mk1, $mk2) = $rd['rel']['map'];
        $this->where($rd['rel']['fk'], (new NJQuery($mt))->select($mk2)->where($mk1, $rd['data']));
      }
    }

    $driver = NJORM::driver();
    if(empty($this->_expr_sel)) {
      $this->_expr_sel = $this->_table->columns($this->_sel_cols);
    }

    $arr = array(
      ':top' => '',
      ':cols' => ' '.$this->_expr_sel->stringify(),
      ':table' => ' '.$this->_table->name(),
      ':cond' => '',
      ':sort' => '',
      ':limit' => '',
      );
    if($this->_cond_limit) {
      if($this->_cond_limit->isTop())
        $arr[':top'] = ' ' . $this->_cond_limit->stringify();
      else
        $arr[':limit'] = ' ' . $this->_cond_limit->stringify();
    }

    if($this->_cond_where) {
      $arr[':cond'] = ' '.$this->_cond_where->whereString();
    }

    if($this->_cond_sort) {
      $arr[':sort'] = ' '.$this->_cond_sort->stringify();
    }

    $sql = strtr('SELECT:top:cols FROM:table:cond:sort:limit', $arr);

    return $sql;
  }

  protected $_last_stmt;
  protected $_last_stmt_md5;

  public function __call($name, $args) {
    $method = str_replace(array('fetch','load'), 'get', $name);

    // fetch one
    if(in_array($method, array('getOne', 'get', 'one'))) {
      return $this->_fetch()->_fetchOne($this->_last_stmt);
    }

    // fetch all
    if(in_array($method, array('getAll', 'getMany', 'all', 'many'))) {
      return $this->_fetch()->_fetchMany($this->_last_stmt);
    }

    // fetch column
    if(in_array($method, array('getCol', 'getColumn', 'col', 'column'))) {
      return call_user_func_array(array($this, '_fetchCol'), $args);
    }

    // fetch grouped pairs
    if(in_array($method, array('getGroupedPairs', 'groupedPairs', 'grouped'))){
      return call_user_func_array(array($this, '_fetchGroupedPairs'), $args);
    }

    // fetch pairs
    if(in_array($method, array('pairs', 'getPairs'))) {
      return call_user_func_array(array($this, '_fetchPairs'), $args);
    }

    trigger_error("NJQuery::{$name}() undefined!");
  }

  protected function _fetchCol($col=0, $unique=false) {
    if($this->_fetch()->_last_stmt) {

      $style = \PDO::FETCH_COLUMN;
      $style |= $unique ? \PDO::FETCH_UNIQUE : 0;

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
  protected function _fetchGroupedPairs($name, $value) {
    $query = clone $this;

    $name = $this->_table->getField($name);
    $value = $this->_table->getField($value);

    // select col1 as name, col2 as value
    $query->_sel_cols = array_map(function($col, $alias){
      return (new NJSql\NJExpr(NJMisc::wrapGraveAccent($col)))
      ->as($alias);
    }
    , array($name, $value)
    , array('name', 'value'));

    if($query->_fetch()->_last_stmt) {
      return $query->_last_stmt->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_COLUMN);
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
  protected function _fetchPairs($name) {
    $query = clone $this;

    // Case 1
    if(func_num_args() > 1) {
      $name = $this->_table->getField($name);
      $value = $this->_table->getField(func_get_arg(1));
      $arr = compact('name', 'value');

      // select col1 as name, col2 as value
      $query->_sel_cols = array_map(function($col, $alias){
        return (new NJSql\NJExpr(NJMisc::wrapGraveAccent($col)))->as($alias);
      }
      , array($name, $value)
      , array('name', 'value'));

      if($query->_fetch()->_last_stmt) {
        return $query->_last_stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
      }
    }

    // Case 2
    // TODO: group by
    else {
      $query->_sel_cols = '*';
      if($query->_fetch()->_last_stmt && $r = $query->_last_stmt->fetchAll(\PDO::FETCH_ASSOC)) {
        $ret = array();
        foreach($r as $v) {
          $ret[$v[$name]] = new NJModel($query->_table, $v);
        }
        return $ret;
      }
    }
  }

  protected function _fetchMany($stmt) {
    if($stmt && $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC)) {
      $_cols = new NJCollection($this->_table, $rs);
      return $_cols;
    }
    return new NJCollection($this->_table, []);
  }

  protected function _fetchOne($stmt) {
    if($stmt && $r = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      return new NJModel($this->_table, $r);
    }
  }

  protected function _fetch() {
    $sql = $this->sqlSelect();
    $params = $this->paramsSelect();
    $stmt_md5 = md5($sql.serialize($params));

    if(!$this->_last_stmt || $this->_last_stmt_md5 != $stmt_md5) {
      $this->_collection = null;
      $this->_model = null;
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
    $stmt = NJDb::execute($sql, $this->parameters());

    if($stmt) {
      return intval($stmt->fetchColumn());
    }
    return 0;
  }

  protected function sqlCount($col) {
    if($this->_rel_data) {
      $rd =& $this->_rel_data;
      $this->where($rd['rel']['fk'], $rd['data']);
    }

    $col or $col = '*';
    $col == '*' or $col = NJMisc::wrapGraveAccent($this->_table->getField($col));
    $sql = sprintf('SELECT COUNT(%s) %s FROM %s'
      , NJMisc::wrapGraveAccent('c')
      , $col
      , $this->_table->name());

    if($this->_cond_where) {
      $sql .= ' '.$this->_cond_where->whereString();
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

    // single => multiple
    if(!is_numeric(implode('', array_keys($data)))){
      $data = array($data);
    }

    // TYPE_RELATION_MANY,
    if($this->_rel_data) {
      $rd =& $this->_rel_data;
      if($rd['rel']['type'] == NJSql\NJTable::TYPE_RELATION_MANY) {
        $data = array_map(function(&$dt) use($rd) {
          $dt[$rd['rel']['fk']] = $rd['data'];
          return $dt;
        }, $data);
      }
    }

    $sql = $this->sqlInsert($data);


    if(in_array(NJORM::driver(), array('mssql', 'dblib'))) {
      $sql = str_replace('VALUES', 'output inserted.' . $this->_table->getField($this->_table->primary()) . ' VALUES', $sql);
    }

    $stmt = NJDb::execute($sql, $this->paramsInsert());

    if(in_array(NJORM::driver(), array('mssql', 'dblib'))) {
      $lastInsertId = $stmt->fetchColumn();
    }
    else {
      $lastInsertId = NJORM::inst()->lastInsertId();
    }

    if(is_numeric($lastInsertId) && ''.intval($lastInsertId) === ''.$lastInsertId) {
      $lastInsertId = floatval($lastInsertId);
    }

    return (new NJModel($this->_table, array($this->_table->primary() => $lastInsertId)))->withLazyReload();
  }

  protected function sqlUpdate($data){
    if($this->_rel_data) {
      $rd =& $this->_rel_data;
      $this->where($rd['rel']['fk'], $rd['data']);
    }

    $this->_type = static::QUERY_TYPE_UPDATE;

    $this->_expr_upd = $this->_table->values($data, true);

    $vsql = $this->_expr_upd->stringify();
    if(!$vsql) return null;

    $sql = 'UPDATE '.$this->_table->name()
      .' SET '.$vsql;

    if($this->_cond_where) {
      $sql .= ' '.$this->_cond_where->whereString();
    }

    if($this->_cond_sort) {
      $sql .= ' '.$this->_cond_sort->stringify();
    }

    if($this->_cond_limit) {
      $sql .= ' '.$this->_cond_limit->stringify();
    }

    return $sql;
  }

  public function update($data){

    $prikey = $this->_table->primary();

    $sql = $this->sqlUpdate($data);

    if($sql) {
      $stmt = NJDb::execute($sql, $this->paramsUpdate());
    }

    return true;
  }


  /****************************************************************************************
   * Deletion
   ****************************************************************************************/
  protected function sqlDelete() {
    if($this->_rel_data) {
      $rd =& $this->_rel_data;
      $this->where($rd['rel']['fk'], $rd['data']);
    }

    $this->_type = static::QUERY_TYPE_DELETE;
    $sql = 'DELETE FROM '.$this->_table->name();

    if($this->_cond_where) {
      $sql .= ' '.$this->_cond_where->whereString();
    }

    if($this->_cond_sort) {
      $sql .= ' '.$this->_cond_sort->stringify();
    }

    if($this->_cond_limit) {
      $sql .= ' '.$this->_cond_limit->stringify();
    }

    return $sql;
  }

  public function delete() {
    if(func_num_args() > 0) {
      return (new NJQuery($this->_table))->where($this->_table->primary(), func_get_arg(0))->delete();
    }

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

  /* jsonSerialize */
  public function jsonSerialize() {
    if($this->_cond_limit
    || $this->_cond_where
    || $this->_cond_sort) {
      return $this->all()->jsonSerialize();
    }
    return $this->one()->jsonSerialize();
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
    return (new NJQuery($this->_table))->where($this->_table->primary(), $offset)->limit(1)->fetchOne();
  }
  public function offsetSetById($offset, $value) {
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
    if(!is_string($offset)
      || $this->_cond_limit
      || $this->_cond_where
      || $this->_cond_sort){
      return $this->offsetExistsByFetch($offset);
    }
    return $this->offsetExistsById($offset);
  }
  public function offsetGet($offset) {
    if(!is_string($offset)
      || $this->_cond_limit
      || $this->_cond_where
      || $this->_cond_sort){
      return $this->offsetGetByFetch($offset);
    }
    return $this->offsetGetById($offset);
  }
  public function offsetSet($offset, $value) {
    if(!is_string($offset)
      || $this->_cond_limit
      || $this->_cond_where
      || $this->_cond_sort){
      return $this->offsetSetByFetch($offset);
    }
    return $this->offsetSetById($offset);
  }
  public function offsetUnset($offset) {
    if(!is_string($offset)
      || $this->_cond_limit
      || $this->_cond_where
      || $this->_cond_sort){
      return $this->offsetUnsetByFetch($offset);
    }
    return $this->offsetUnsetById($offset);
  }
}