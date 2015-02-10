<?php
/**
 * @Author: byamin
 * @Date:   2015-01-01 12:09:20
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-02-11 00:50:47
 */
namespace NJORM;
use \NJORM\NJCom;

class NJQuery implements NJCom\NJStringifiable{
  const QUERY_TYPE_CREATE = 0;
  const QUERY_TYPE_SELECT = 1;
  const QUERY_TYPE_UPDATE = 2;
  const QUERY_TYPE_DELETE = 3;
  protected $_table;
  protected $_type;

  public function __construct($table) {
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
    $strTb =& $this->_table;
    $string = NJTable::$strTb()->select($this->_select['columns']);
    $string .= ' '.NJTable::$strTb()->from();

    if($this->_select['condition']) {
      $this->_select['condition']->setTable(NJTable::$strTb());
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

  public function fetch() {
    echo $this->sqlSelect();return;
    $stmt = NJORM::pdo()->query($this->sqlSelect());
    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
    echo json_encode($result);
  }
}