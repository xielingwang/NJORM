<?php
/**
 * @Author: Amin by
 * @Date:   2014-12-15 10:22:32
 * @Last Modified by:   Amin by
 * @Last Modified time: 2014-12-30 13:54:14
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

  public function setPrimaryKey() {
    if(func_num_args() < 1) {
      trigger_error('NJTable setPrimaryKey() expects at least one argument.', E_USER_ERROR);
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

  public function setAutoIncrement($key) {
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

  public function select_star($alias_tb = null, $dbname = null) {
    $cols = array();
    foreach($this->_fields as $alias => $field) {
      $fieldName = array($field->name);

      if(!empty($alias_tb))
        array_unshift($fieldName, $alias_tb);

      if(!empty($dbname))
        array_unshift($fieldName, $dbname);

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