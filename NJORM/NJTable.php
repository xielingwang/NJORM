<?php
/**
 * @Author: Amin by
 * @Date:   2014-12-15 10:22:32
 * @Last Modified by:   Amin by
 * @Last Modified time: 2015-01-06 10:33:39
 */
namespace NJORM;
use NJORM\NJCom\NJField;
interface INJTable {
  function name();
}

class NJTable implements INJTable {
  /* table creataion */
  protected $_d_name;
  protected $_d_pri_key;
  protected $_d_auto_increment;
  protected $_d_fields = array();

  /* table query */
  protected $_q_as;
  protected $_q_selection;

  public function primaryKey() {
    if(func_num_args() < 1) {
      return $this->_d_pri_key;
    }

    $this->_d_pri_key = func_get_args();
    foreach($this->_d_pri_key as &$pkey) {

      if(!($field = $this->field_by_name($pkey))) {
        if(array_key_exists($key, $this->_d_fields))
          $field = $this->_d_fields[$key];
      }

      if(!$field) {
        trigger_error(sprintf('Field key "%s" undefined!', $key), E_USER_ERROR);
      }

      $pkey = $field->name;
    }
    return $this;
  }

  public function field_by_name($key) {
    foreach($this->_d_fields as $field) {
      if($field->name == $key) {
        return $field;
      }
    }

    return false;
  }

  public function autoIncrement($key) {
    if(!($field = $this->field_by_name($key))) {
      if(array_key_exists($key, $this->_d_fields))
        $field = $this->_d_fields[$key];
    }

    if(!$field) {
      trigger_error(sprintf('Field key "%s" undefined!', $key), E_USER_ERROR);
    }

    $field->auto_increment();
    return $this;
  }

  public function __get($name) {
    if($name == 'name')
      return sprintf('`%s`', $this->_d_name);
    trigger_error(sprintf('undefined "%s" in NJTable.', $name));
  }

  public function __call($name, $args) {
    if($name == 'as'){
      return call_user_func_array(array($this, '_as'), $args);
    }
    trigger_error('Call undefined function: '.$name, E_USER_ERROR);
  }

  protected function _as($tbAs) {
    $this->_q_as = $tbAs;
    return $this;
  }

  public function select(){
    $this->_q_selection = func_get_args();
    return $this;
  }

  public function selectionString() {
    $args = $this->_q_selection;
    $select_cols = array();

    $tbAs = null;
    if($this->_q_as) {
      $tbAs = $this->_q_as;
      $this->_q_as = null;
    }

    $field_aliases = array_keys($this->_d_fields);
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
    foreach($select_cols as $fAlias) {
      if(!is_scalar($fAlias)) {
        trigger_error('Unexpected type of value for NJTable::select()', E_USER_ERROR);
      }

      if(!in_array($fAlias, $field_aliases)) {
        trigger_error(sprintf('Field %s have not defined in table.', $fAlias), E_USER_ERROR);
      }

      $field = $this->_d_fields[$fAlias];
      $fieldName = array($field->name);
      if($tbAs) {
        array_unshift($fieldName, $tbAs);
      }
      $col = NJMisc::field_standardize($fieldName);
      if($field->name != $fAlias)
        $col .= ' ' . NJMisc::field_standardize($fAlias);
      $cols[] = $col;
    }

    return 'SELECT ' . implode(',', $cols);
  }

  
  
  
  /**
   * set/add table field
   * 
   * 1. field('name', 'alias');
   * 2. field('name', 'alias', $field);
   * 3. field('name', $field);
   * 4. field('name');
   * 
   * @param  [type] $name [description]
   * @return [type]       [description]
   */
  public function field($name) {
    do {
      if(func_num_args() <= 1)
        break;

      $arg1 = func_get_arg(1);
      if(is_string($arg1)) {
        // 1. match 'name,alias'
        $alias = $arg1;
        if(func_num_args() <= 2)
          break;

        // 2. try to match 'name,alias,njfield'
        $arg2 = func_get_arg(2);
        if(!($arg2 instanceof NJField)) {
          trigger_error('The 3nd argument for NJTable::field()expects NJField instance.');
        }
        $field = $arg2;
        break;
      }

      // 3. try to match 'name,field'
      if($arg1 instanceof NJField) {
        $field = $arg1;
        break;
      }

      trigger_error('The 2nd argument for NJTable::field()expects NJField or string instance .');
    }
    while(0);

    // 4. match 'name'
    if(empty($field)) {
      $field = new NJField($this);
    }
    $field->name($name);

    empty($alias) && $alias = $name;

    return $this->_d_fields[$alias] = $field;
  }

  /**
   * Show Create Table
   * @return [type] [description]
   */
  public function showCreateTable($tbprefix = "test_", $dropIfExists = false) {
    $tbname = $tbprefix . $this->_d_name;

    // defines
    $string = sprintf("CREATE TABLE `%s`", $tbname);
    $defines = array();

    // fields
    foreach ($this->_d_fields as $alias => $field) {
      $stmt = $field->toDefine();
      if(in_array($this->_d_auto_increment, array($alias, $field->name))) {
        $stmt .= ' AUTO_INCREMENT';
      }
      $defines[] = $stmt;
    }

    // primary key
    if(!empty($this->_d_pri_key)) {
      $prikeys = array();
      foreach ($this->_d_pri_key as $key) {
        if(array_key_exists($key, $this->_d_fields))
          $key = $this->_d_fields[$key]->name;
        $prikeys[] = NJMisc::field_standardize($key);
      }
      $defines[] = 'PRIMARY KEY ('.implode(',', $prikeys).')';
    }

    // indexes

    // finish table defines
    $string .= "(\n".implode(",\n", $defines)."\n)";

    // table attributes
    $string .= ";";

    // DROP: drop statement
    if($dropIfExists) {
      $string = sprintf("DROP TABLE IF EXISTS `%s`;\n", $tbname) . $string;
    }

    return $string;
  }

  public function __construct($name){
    $this->_d_name = $name;
  }

  public function name() {
    return $this->_d_name;
  }

  public static function factory($name) {
    return new INJTable($name);
  }
}