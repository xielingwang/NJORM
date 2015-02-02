<?php
/**
 * @Author: byamin
 * @Date:   2015-02-02 23:27:30
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-02-03 00:33:30
 */

namespace NJORM;

class NJTable {
  protected $_primary;
  protected $_fields;

  public function __construct($alias) {
    $this->_alias = $alias;
  }

  public function columns($cols='*', $ta=null, $da=null) {
    if(is_string($cols)) {
      $cols = explode(',', $cols);
    }
    if(in_array('*', $cols)) {
      array_splice($cols, array_search('*', $cols), 1, array_keys($this->_fields));
      $cols = array_unique($cols);
    }
    $newcols = array();
    foreach($cols as $col) {
      if(array_key_exists($col, $this->_fields)) {
        $col = sprintf("`%s` `%s`", $this->_fields[$col], $col);
      }
      elseif(in_array($col, $this->_fields)) {
        $col = sprintf("`%s` `%s`", $col, array_search($col, $this->_fields));
      }

      if($ta) {
        $col = sprintf('`%s`.%s', $ta, $col);
      }
      if($da) {
        $col = sprintf('`%s`.%s', $da, $col);
      }

      $newcols[] = $col;
    }
    return implode(',', $newcols);
  }

  protected static $_tables = array();
  static function define($name, $alias) {
    if(!array_key_exists($name, static::$_tables))
      static::$_tables[$name] = new static($alias);
    return static::$_tables[$name];
  }
}