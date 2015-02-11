<?php
/**
 * @Author: byamin
 * @Date:   2015-01-01 12:09:20
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-02-12 00:44:34
 */
namespace NJORM;
use \NJORM\NJCom;

class NJQuery implements NJCom\NJStringifiable,\Countable,\ArrayAccess{
  const QUERY_TYPE_CREATE = 0;
  const QUERY_TYPE_SELECT = 1;
  const QUERY_TYPE_UPDATE = 2;
  const QUERY_TYPE_DELETE = 3;
  protected $_table;
  protected $_type;

  public function __construct($table) {
    if(is_string($table))
      $table = NJTable::$table();
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

  // read
  protected $_select = array(
    'columns' => array('*'),
    'limit' => array(),
    'condition' => null,
    'orderby' => null,
    );
  public function select() {
    $this->_type = static::QUERY_TYPE_SELECT;
    $this->_select['columns'] = func_get_args();
    return $this;
  }

  public function limit() {
    $this->_select['limit'] = func_get_args();
    return $this;
  }

  public function where($arg) {
    NJCom\NJCondition::setTable($this->_table);
    if(!($arg instanceof NJCom\NJCondition))
      $arg = NJCom\NJCondition::fact(func_get_args());
    if($this->_select['condition'] instanceof NJCom\NJCondition) {
      $this->_select['condition']->and($arg);
    }
    else {
      $this->_select['condition'] = $arg;
    }
    return $this;
  }

  public function sortAsc() {
    if(is_null($this->_select['orderby']))
      $this->_select['orderby'] = new NJCom\NJOrderby();

    foreach(func_get_args() as $field) {
      $this->_select['orderby']->add($field, true);
    }
    return $this;
  }

  public function sortDesc() {
    if(is_null($this->_select['orderby']))
      $this->_select['orderby'] = new NJCom\NJOrderby();

    foreach(func_get_args() as $field) {
      $this->_select['orderby']->add($field, false);
    }
    return $this;
  }

  public function sqlSelect() {
    $string = $this->_table->select($this->_select['columns']);
    $string .= ' '.$this->_table->from();

    if($this->_select['condition']) {
      $string .= ' '.$this->_select['condition'];
    }

    if($this->_select['orderby']) {
      $string .= ' ' . $this->_select['orderby'];
    }

    if($this->_select['limit']) {
      $string .= ' ' . call_user_func_array(__NAMESPACE__.'\NJCom\NJLimit::factory', $this->_select['limit']);
    }
    return $string;
  }

  public function sqlCount() {
    return '';
  }

  public function fetch() {
    $sql = $this->sqlSelect();
    $stmt = NJORM::pdo()->query($sql);
    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
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
}