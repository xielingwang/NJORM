<?php
/**
 * @Author: byamin
 * @Date:   2015-02-02 23:27:30
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-02-17 20:26:00
 */

namespace NJORM\NJSql;
use NJORM\NJMisc;

define('FK_FMT', 'id_{tbname}');
class NJTable {
  protected $_name;
  protected $_pri_key;
  protected $_fields;

  public function __construct($name) {
    $this->_name = $name;
  }

  public function __get($name) {
    return $this->columns($name);
  }

  public function name($alias = null) {
    if($alias) {
      return sprintf("`%s` `%s`", $this->_name, $alias);
    }

    return sprintf("`%s`", $this->_name);
  }

  public function getField($field) {
    if(array_key_exists($field, $this->_fields))
      return $field;
    if($f = array_search($field, $this->_fields))
      return $f;
    trigger_error(sprintf('NJTable::getField("%s") error for table: %s', $this->_name, $field));
  }

  public function field($field, $alias = null) {
    if(!$this->_fields)
      $this->_fields = array();
    $this->_fields[$field] = $alias;
    return $this;
  }

  public function primary($field, $alias = null) {
    $this->field($field, $alias);

    if(!$this->_pri_key)
      $this->_pri_key = $field;
    elseif(is_string($this->_pri_key)) {
      $this->_pri_key = array($this->_pri_key, $field);
    }
    else {
      array_unshift($this->_pri_key, $field);
    }
    return $this;
  }

  public function defined_primary($field) {
    return in_array($field, (array)$this->_pri_key);
  }

  public static function check_field_exist($table, $field) {
    if(is_array($field)) {
      foreach ($field as $f) {
        static::check_field_exist($table, $f);
      }
      return;
    }
    if(!in_array($field, $table->_fields) && !array_key_exists($field, $table->_fields)) {
      trigger_error(sprintf('Field "%s" is not in table "%s"', $field, $table->_name));
    }
  }
  protected static function get_real_field($table, $field, $foreign_table = null) {
    if(!$field) {
      $field = $foreign_table ? static::fk_for_table($foreign_table) : $table->_pri_key;
    }

    // check field exist
    static::check_field_exist($table, $field);

    // get field in database
    if(is_array($field) && count($field)==1)
      $field = $field[0];
    if(is_array($field)) {
      foreach ($field as &$f) {
        $f = static::get_real_field($table, $f);
      }
    }
    elseif(array_key_exists($field, $table->_fields)) {
      $field = $table->_fields[$field];
    }
    return $field;
  }

  // relationships
  const TYPE_RELATION_ONE = 1;
  const TYPE_RELATION_MANY = 2;
  const TYPE_RELATION_MANY_X = 3;
  protected $_has = array();
  function rel($table) {
    if(!array_key_exists($table, $this->_has)) {
      trigger_error(sprintf('Undefined relationship "" for "%s"', $table, $this->_name));
    }
    return $this->_has[$table];
  }

  function hasOne($sk, $table, $fk) {
    $fk || $fk = static::$table()->_pri_key;
    $sk || $sk = $this->_pri_key;

    $fk = static::get_real_field(static::$table(), $fk);
    $sk = static::get_real_field($this, $sk);

    // ok
    $this->_has[$table] = array(
      'type' => static::TYPE_RELATION_ONE,
      'fk' => $fk,
      'sk' => $sk,
      );

    return $this;
  }
  function hasMany($sk, $table, $fk, $msk=null, $mfk=null, $mapTable=null) {
    $sk = static::get_real_field($this, $sk);

    if(func_num_args() <= 3) {
      $fk = static::get_real_field(static::$table(), $fk, $this);

      $this->_has[$table] = array(
        'type' => static::TYPE_RELATION_MANY,
        'fk' => $fk,
        'sk' => $sk,
        );
      return $this;
    }
    else {
      $fk = static::get_real_field(static::$table(), $fk);
      $msk = static::get_real_field(static::$mapTable(), $msk, $this);
      $mfk = static::get_real_field(static::$mapTable(), $mfk, static::$table());

      $this->_has[$table] = array(
        'type' => static::TYPE_RELATION_MANY_X,
        'map' => $mapTable,
        'sk' => $sk,
        'msk' => $msk,
        'fk' => $fk,
        'mfk' => $mfk,
        );
    }
    return $this;
  }

  // defines
  protected static $_tables = array();
  protected static $_aliases = array();
  static function defined($name) {
    return array_key_exists($name, static::$_tables)
      || array_key_exists($name, static::$_aliases);
  }
  static function define($name, $alias=null) {
    if(!array_key_exists($name, static::$_tables)) {
      static::$_tables[$name] = new static($name);
      if($alias) {
        static::$_aliases[$alias] =& static::$_tables[$name];
      }
    }
    else
      trigger_error('Table has been exists: ' . $name);

    return static::$_tables[$name];
  }
  static function __callStatic($name, $arguments) {
    if(array_key_exists($name, static::$_tables)){
      return static::$_tables[$name];
    }
    if(array_key_exists($name, static::$_aliases)){
      return static::$_aliases[$name];
    }
    trigger_error('Table is undefined: ' . $name);
  }
  public static function fk_for_table($table) {
    return strtr(FK_FMT, array('{tbname}' => $table->_name));
  }

  // SQL generator
  public function columns($cols='*', $whichTable=null, $whichDB=null) {
    $whichTable === true && $whichTable = $this->_name;
    if(is_string($cols)) {
      $cols = explode(',', $cols);
    }
    if(in_array('*', $cols)) {
      array_splice($cols, array_search('*', $cols), 1, array_values($this->_fields));
      $cols = array_unique($cols);
    }
    $newcols = array();
    $flip_fields = array_flip($this->_fields);
    foreach($cols as $col) {
      if(array_key_exists($col, $this->_fields)) {
        $col = sprintf("`%s`", $col);
      }
      elseif(array_key_exists($col, $flip_fields)) {
        $col = sprintf("`%s` `%s`", $flip_fields[$col], $col);
      }
      else {
        trigger_error(sprintf('Undefined field - "%s"', $col));
      }

      // in which table?
      if($whichTable) {
        $col = sprintf('`%s`.%s', $whichTable, $col);
      }

      // in which database?
      if($whichDB) {
        $col = sprintf('`%s`.%s', $whichDB, $col);
      }

      $newcols[] = $col;
    }
    return implode(',', $newcols);
  }

  public function values($values) {
    return $this->valuesIns($values);
  }

  protected function valuesIns($values) {
    // translate one record to multiple records
    if(!is_array(current($values))) {
      return $this->valuesIns(array($values));
    }

    $inputKeys = array();
    foreach ($values as $val) {
      $inputKeys = array_merge($inputKeys, array_keys($val));
    }
    $flip_fields = array_flip($this->_fields);

    // unique fields and remove that field not in table fields or field aliases
    $fields = array();
    foreach (array_unique($inputKeys) as $col) {
      if(array_key_exists($col, $flip_fields))
        $fields[] = $flip_fields[$col];
      elseif(array_key_exists($col, $this->_fields)) {
        $fields[] = $col;
      }
    }
    $engraved_fields = array_map(function($f){return '`'.$f.'`';}, $fields);

    // values
    $fmted_vals = array();
    foreach ($values as $val) {
      $tmpArr = array();
      foreach($fields as $field) {
        if(array_key_exists($field, $val)){
          $v = $val[$field];
        }
        elseif(array_key_exists($this->_fields[$field], $val)) {
          $v = $val[$this->_fields[$field]];
        }
        else {
          $v = NULL;
        }
        $tmpArr[] = NJMisc::formatValue($v);
      }
      $fmted_vals[] = '('.implode(',', $tmpArr).')';
    }

    return sprintf('(%s) VALUES %s'
      , implode(',', $engraved_fields)
      , implode(',', $fmted_vals));
  }
}