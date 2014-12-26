<?php
/**
 * @Author: Amin by
 * @Date:   2014-12-15 10:22:32
 * @Last Modified by:   Amin by
 * @Last Modified time: 2014-12-26 14:17:18
 */
namespace NJORM;
interface INJTable {
  function name();
}

class NJTable implements INJTable {
  protected $_name;
  protected $_pri_key = 'sa1,sa2';
  protected $_fields = array(
    'sa1' => array(
      'name' => 'pk1',
      'type' => 'INT(11) unsigned',
      'notnull' => true,
      'default' => 1,
      'comment' => '这是个注释',
      ),
    'sa2' => array(
      'name' => 'pk2',
      'type' => 'INT(11)'
      ),
    'sa3' => array(
      'name' => 'longkey1',
      'type' => 'VARCHAR(256)'
      ),
    );

  public function select_star() {
    $cols = array();
    foreach($this->_fields as $alias => $fi) {
      $cols[] = sprintf('`%s` `%s`', $fi['name'], $alias);
    }
    return 'SELECT ' . implode(',', $cols);
  }

  public function field($alias, $name, $type) {
    $field =& $this->_fields[$alias];
    $field = array(
      'name' => $name,
      'type' => $type,
      );
    if(func_num_args() > 3) {
      if(is_bool(func_get_arg(3))) {
        $field['notnull'] = func_get_arg(3);
        if(func_num_args()>4)
          $field['default'] = func_get_arg(4);
        if(func_num_args()>5)
          $field['comment'] = func_get_arg(5);
      }
      else {
        $field['comment'] = func_get_arg(3);
      }
    }
    return $this;
  }

  public function __toString() {
    $tbprefix = "test_";
    $tbname = $tbprefix . $this->_name;
    $string = sprintf("CREATE TABLE `%s`", $tbname);
    $defines = array();

    // fields
    foreach ($this->_fields as $fi) {
      $stmt = sprintf("`%s` %s", $fi['name'], $fi['type']);
      if(array_key_exists('notnull', $fi) && $fi['notnull'] === true) {
        $stmt .= " NOT NULL";
      }
      if(array_key_exists('default', $fi)) {
        $defVal = $fi['default'];
        if(is_null($defVal))
          $defVal = 'notnull';
        elseif(!is_numeric($defVal))
          $defVal = "'".$defVal."'";
        $stmt .= " DEFAULT " . $defVal;
      }
      if(array_key_exists('comment', $fi)) {
        $stmt .= sprintf(" COMMENT '%s'", addslashes($fi['comment']));
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