<?php
/**
 * @Author: Amin by
 * @Date:   2014-12-15 10:22:32
 * @Last Modified by:   byamin
 * @Last Modified time: 2014-12-31 01:14:08
 */
namespace NJORM;
use NJORM\NJCom\NJField;
interface INJTable {
  function name();
}

class NJTable implements INJTable {
  protected $_name;
  protected $_pri_key;
  protected $_auto_increment;
  protected $_fields = array();

  public function primaryKey() {
    if(func_num_args() < 1) {
      trigger_error('NJTable::primaryKey() expects at least one argument.', E_USER_ERROR);
    }

    $this->_pri_key = func_get_args();
    foreach($this->_pri_key as &$pkey) {

      if(!($field = $this->field_by_name($pkey))) {
        if(array_key_exists($key, $this->_fields))
          $field = $this->_fields[$key];
      }

      if(!$field) {
        trigger_error(sprintf('Field key "%s" undefined!', $key), E_USER_ERROR);
      }

      $pkey = $field->name;
    }
    return $this;
  }

  public function field_by_name($key) {
    foreach($this->_fields as $field) {
      if($field->name == $key) {
        return $field;
      }
    }

    return false;
  }

  public function autoIncrement($key) {
    if(!($field = $this->field_by_name($key))) {
      if(array_key_exists($key, $this->_fields))
        $field = $this->_fields[$key];
    }

    if(!$field) {
      trigger_error(sprintf('Field key "%s" undefined!', $key), E_USER_ERROR);
    }

    $field->auto_increment();
    return $this;
  }

  public function __call($name, $args) {
    if($name == 'as'){
      return call_user_func_array(array($this, '_as'), $args);
    }
    trigger_error('Call undefined function: '.$name, E_USER_ERROR);
  }

  protected $_with_table_alias = null;
  protected function _as($table_alias) {
    $this->_with_table_alias = $table_alias;
  }

  public function select() {
    $args = func_get_args();
    $select_cols = array();

    $table_alias = null;
    if($this->_with_table_alias) {
      $table_alias = $this->_with_table_alias;
      $this->_with_table_alias = null;
    }

    $field_aliases = array_keys($this->_fields);
    foreach($args as $arg) {
      if($arg == '*') {
        $select_cols = array_merge($select_cols, $field_aliases);
      }
      elseif(is_string($arg)) {
        if(!in_array($arg, $field_aliases))
          trigger_error(sprintf('"%s" is not a available key.', $arg), E_USER_ERROR);
        $select_cols[] = $arg;
      }
      else {
        $select_cols[] = $arg;
      }
    }
    $select_cols = array_unique($select_cols);
    $cols = array();
    foreach($select_cols as $col) {
      if(!is_scalar($col)) {
        trigger_error('Unexpected type of value for NJTable::select()', E_USER_ERROR);
      }

      print_r($field_aliases);
      if(!in_array($col, $field_aliases)) {
        trigger_error(sprintf('Field %s have not defined in table.', $col), E_USER_ERROR);
      }

      $field = $this->_fields[$col];
      $fieldName = array($field->name);
      if($table_alias) {
        array_unshift($fieldName, $table_alias);
      }
      $cols[] = implode(' ', array(NJMisc::field_standardize($fieldName), NJMisc::field_standardize($alias)));
    }

    return 'SELECT ' . implode(',', $cols);
  }

  // field('name', $field);
  // field('name', 'nm', $field);
  // field('name');
  // field('name', 'nm');
  public function field($name) {
    do {
      if(func_num_args() <= 1)
        break;

      $arg1 = func_get_arg(1);
      if(is_string($arg1)) {
        $alias = func_get_arg(1);
        if(func_num_args() > 2 && (func_get_arg(2) instanceof NJField)) {
          $field = func_get_arg(2)->name($name);
        }
      }
      elseif(func_get_arg(1) instanceof NJField) {
        $field = func_get_arg(1)->name($name);
      }
    }
    while(0);

    if(empty($field)) {
      $field = (new NJField($this))->name($name);
    }

    empty($alias) && $alias = $name;

    return $this->_fields[$alias] = $field;
  }

  public function toDefine() {
    $tbprefix = "test_";
    $tbname = $tbprefix . $this->_name;
    $string = sprintf("CREATE TABLE `%s`", $tbname);
    $defines = array();

    // fields
    foreach ($this->_fields as $alias => $field) {
      $stmt = $field->toDefine();
      if(in_array($this->_auto_increment, array($alias, $field->name))) {
        $stmt .= ' AUTO_INCREMENT';
      }
      $defines[] = $stmt;
    }

    // primary key
    if(!empty($this->_pri_key)) {
      $prikeys = array();
      foreach ($this->_pri_key as $key) {
        if(array_key_exists($key, $this->_fields))
          $key = $this->_fields[$key]->name;
        $prikeys[] = NJMisc::field_standardize($key);
      }
      $defines[] = 'PRIMARY KEY ('.implode(',', $prikeys).')';
    }

    // indexes

    // finish table defines
    $string .= "(\n".implode(",\n", $defines)."\n)";

    // table attributes
    $string .= ";";

    return $string;
  }


  public function __construct($name){
    $this->_name = $name;
  }

  public function name() {
    return $this->_name;
  }

  public static function factory($name) {
    return new INJTable($name);
  }
}