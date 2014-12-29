<?php
/**
 * @Author: Amin by
 * @Date:   2014-12-15 10:22:32
 * @Last Modified by:   byamin
 * @Last Modified time: 2014-12-30 00:34:01
 */
namespace NJORM;
use NJCom\NJField;
interface INJTable {
  function name();
}

class NJTable implements INJTable {
  protected $_name;
  protected $_pri_key;
  protected $_auto_increment;
  protected $_fields = array();

  public function setPrimaryKey($key) {
    if(func_num_args() < 1) {
      trigger_error('NJTable setPrimaryKey() expects at least one argument.');
    }

    $this->_pri_key = func_get_args();
    foreach($this->_pri_key as $pk) {
      if(!in_array($pk, $this->field_names())) {
        trigger_error('primary key "%s" should be in defined fields.');
      }

    }
    return $this;
  }

  public function field_by_name($key) {
    foreach($this->_fields as $field) {
      if($field->name == $key) {
        return $field;
      }
    }
  }

  public function setAutoIncrement($key) {
    if(!in_array($this->_pri_key)) {
      trigger_error('auto_increment must primary key.');
    }

    foreach($this->_fields as $field) {
      if($field->name != $key)
        continue;

      $field_type = $field->type;
      if(strpos('INT', $field_type) === false || strpos('FLOAT', $field_type) === false || strpos('DOUBLE', $field_type) === false) {
        trigger_error('auto_increment field expects int/float/double type.');
      }
    }
  }

  public function select_star($alias_tb = null, $dbname = null) {
    $cols = array();
    foreach($this->_fields as $alias => $field_define) {
      $field = array($field_define['name']);

      if(!empty($alias_tb))
        array_unshift($field, $alias_tb);

      if(!empty($dbname))
        array_unshift($field, $dbname);

      $cols[] = implode(' ', array(NJMisc::field_standardize($field), NJMisc::field_standardize($alias)));
    }
    return 'SELECT ' . implode(',', $cols);
  }

  // field('name', $field);
  // field('name', 'nm', $field);
  // field('name');
  // field('name', 'nm');
  public function field($name) {
    $arg1 = func_get_arg(1);
    if(is_string($arg1)) {
      $alias = func_get_arg(1);
      if(func_get_arg(2) instanceof NJField) {
        $field = func_get_arg(2)->setName($name);
      }
    }
    elseif(func_get_arg(1) instanceof NJField) {
      $field = func_get_arg(1)->setName($name);
    }

    if(empty($field)) {
      $field = (new NJField($this))->setName($name);
    }

    empty($alias) && $alias = $name;

    return $this->_fields[$alias] = $field;
  }

  public function __toString() {
    $tbprefix = "test_";
    $tbname = $tbprefix . $this->_name;
    $string = sprintf("CREATE TABLE `%s`", $tbname);
    $defines = array();

    // fields
    foreach ($this->_fields as $alias => $field) {
      $stmt = (string)$field;
      if(in_array($this->_auto_increment, array($alias, $field->name))) {
        $stmt .= ' AUTO_INCREMENT';
      }
      $defines[] = $stmt;
    }

    // primary key
    if(!empty($this->_pri_key)) {
      $prikeys = explode(',', $this->_pri_key);
      foreach ($prikeys as &$key) {
        if(array_key_exists($key, $this->_fields))
          $key = $this->_fields[$key]['name'];
        $key = '`' . $key . '`';
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