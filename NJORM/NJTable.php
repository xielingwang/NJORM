<?php
/**
 * @Author: byamin
 * @Date:   2015-02-02 23:27:30
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-02-04 01:00:58
 */

namespace NJORM;

class NJTable {
  protected $_name;
  protected $_primary;
  // field => alias
  protected $_fields;

  public function __construct($name) {
    $this->_name = $name;
  }

  public function __get($name) {
    return $this->columns($name, $this->_alias);
  }

  public function columns($cols='*', $ta=null, $da=null) {
    $ta === true && $ta = $this->_name;
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
        $col = sprintf("`%s` `%s`", array_search($col, $this->_fields), $col);
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

  public function field($field, $alias) {
    if(!$this->_fields)
      $this->_fields = array();
    $this->_fields[$field] = $alias;
    return $this;
  }

  public function primary($field, $alias) {
    $this->field($field, $alias);

    if(!$this->_primary)
      $this->_primary = array();
    array_unshift($this->_primary, $field);
    return $this;
  }

  protected static $_tables = array();
  static function define($name, $alias) {
    if(!array_key_exists($alias, static::$_tables))
      static::$_tables[$alias] = new static($name);
    else
      trigger_error('table alias exists ' . $alias);

    return static::$_tables[$alias];
  }
}