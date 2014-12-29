<?php
/**
 * @Author: Amin by
 * @Date:   2014-12-15 10:22:32
 * @Last Modified by:   Amin by
 * @Last Modified time: 2014-12-29 17:13:33
 */
namespace NJORM;
use NJCom\NJField;
interface INJTable {
  function name();
}

class NJTable implements INJTable {
  protected $_name;
  protected $_pri_key = 'sa1,sa2';
  protected $_auto_increment = 'sa1';
  protected $_fields = array();

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

  public function field($alias, NJField $field = null) {
    if($field instanceof NJField)
      $this->_field[$alias] = $field;
    else
      $this->_field[$alias] = new NJField($this);

    return $this->_field[$alias];
  }

  public function __toString() {
    $tbprefix = "test_";
    $tbname = $tbprefix . $this->_name;
    $string = sprintf("CREATE TABLE `%s`", $tbname);
    $defines = array();

    // fields
    foreach ($this->_fields as $alias => $field) {
      $defines = (string)$field;
      if($this->_auto_increment)
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