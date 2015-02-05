<?php
/**
 * @Author: byamin
 * @Date:   2015-02-02 23:27:30
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-02-05 08:46:01
 */

namespace NJORM;

class NJTable {
  protected $_name;
  protected $_primary;
  protected $_fields;

  public function __construct($name) {
    $this->_name = $name;
  }

  public function __get($name) {
    return $this->columns($name);
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
      $this->_primary = $field;
    elseif(is_string($this->_primary)) {
      $this->_primary = array($this->_primary, $field);
    }
    else {
      array_unshift($this->_primary, $field);
    }
    return $this;
  }

  protected static function check_field_exist($table, $field) {
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
  protected static function get_real_field($table, $field) {
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
  function hasOne($table, $fk=null, $sk=null) {
    $fk || $fk = static::$table()->_primary;
    $sk || $sk = $this->_primary;

    static::check_field_exist(static::$table(), $fk);
    $fk = static::get_real_field(static::$table(), $fk);

    static::check_field_exist($this, $sk);
    $sk = static::get_real_field($this, $sk);

    // ok
    $this->_has[$table] = array(
      'type' => TYPE_RELATION_ONE,
      'fk' => $fk,
      'sk' => $sk,
      );

    return $this;
  }
  function hasMany($table, $fk = null, $sk = null, $map = null, $msk=null, $mfk=null) {
    $sk || $sk = $this->_primary;
    $fk || $fk = static::$table()->_primary;

    static::check_field_exist(static::$table(), $fk);
    $fk = static::get_real_field(static::$table(), $fk);

    static::check_field_exist($this, $sk);
    $sk = static::get_real_field($this, $sk);

    if(func_num_args() <= 3) {
      $this->_has[$table] = array(
        'type' => TYPE_RELATION_MANY,
        'fk' => $msk,
        'sk' => $sk,
        );
      return $this;
    }
    else {
      $msk || $msk = 'id_'.$this->_name;
      $mfk || $mfk = 'id_'.static::$table()->_name;

      static::check_field_exist(static::$map(), $msk);
      $msk = static::get_real_field(static::$map(), $msk);

      static::check_field_exist(static::$map(), $mfk);
      $mfk = static::get_real_field(static::$map(), $mfk);

      $this->_has[$table] = array(
        'type' => TYPE_RELATION_MANY_X,
        'map' => $map,
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
  static function define($name) {
    if(!array_key_exists($name, static::$_tables))
      static::$_tables[$name] = new static($name);
    else
      trigger_error('Table has been exists: ' . $name);

    return static::$_tables[$name];
  }
  static function __callStatic($name, $arguments) {
    if(array_key_exists($name, static::$_tables)){
      return static::$_tables[$name];
    }
    trigger_error('Table is undefined: ' . $name);
  }
}