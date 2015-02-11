<?php
/**
 * @Author: byamin
 * @Date:   2015-02-02 23:27:30
 * @Last Modified by:   Amin by
 * @Last Modified time: 2015-02-11 15:18:04
 */

namespace NJORM;

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

  public function from() {
    return 'FROM ' . call_user_func_array(array($this, 'name'), func_get_args());
  }
  public function select() {
    return 'SELECT ' . call_user_func_array(array($this, 'columns'), func_get_args());
  }
  public function columns($cols='*', $tb_alias=null, $db_alias=null) {
    $tb_alias === true && $tb_alias = $this->_name;
    if(is_string($cols)) {
      $cols = explode(',', $cols);
    }
    if(in_array('*', $cols)) {
      array_splice($cols, array_search('*', $cols), 1, array_keys($this->_fields));
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

      if($tb_alias) {
        $col = sprintf('`%s`.%s', $tb_alias, $col);
      }
      if($db_alias) {
        $col = sprintf('`%s`.%s', $db_alias, $col);
      }

      $newcols[] = $col;
    }
    return implode(',', $newcols);
  }

  public function getField($field) {
    return static::get_real_field($this, $field);
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
    elseif(in_array($field, $table->_fields)) {
      $field = array_search($field, $table->_fields);
    }
    return $field;
  }

  // relationships
  const TYPE_RELATION_ONE = 1;
  const TYPE_RELATION_MANY = 2;
  const TYPE_RELATION_MANY_X = 3;
  protected $_has = array();
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
}