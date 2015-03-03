<?php
/**
 * @Author: byamin
 * @Date:   2015-02-02 23:27:30
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-04 00:37:36
 */

namespace NJORM\NJSql;
use NJORM\NJMisc;
use NJORM\NJValid;

class NJTable {
  protected $_name;
  protected $_pri_key;
  protected $_fields;

  const FK_FMT = 'id_{tbname}';

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

    // for static::valid()
    $this->_prev_field = $field;
    $this->_fields[$field] = $alias;
    return $this;
  }

  private $_prev_field;
  protected $_validation = array();
  public function valid($message) {
    if(!$this->_prev_field) {
      trigger_error('Field should be define first!');
    }
    $valids = func_get_args();
    array_shift($valids);
    if(empty($valids)) {
      trigger_error('NJTable::valid() expects more than 1 arguments.');
    }
    foreach($valids as &$_vld) {
      $vld = $_vld;
      if(!($vld instanceof NJValid)) {
        if(!is_array($vld)) {
          $vld = array($vld);
        }
        $tmp = array($this->_fields[$this->_prev_field], $this->_name);
        $_vld = function ($data) use ($vld,$tmp) {
          array_unshift($tmp, $data);
          $vld = array_merge($vld, $tmp);
          return NJValid::V($vld);
        };
      }
    }
    if(!array_key_exists($this->_prev_field, $this->_validation)){
      $this->_validation[$this->_prev_field] = array();
    }
    $this->_validation[$this->_prev_field][] = array(
      'msg' => $message,
      'valids' => $valids,
      );
    return $this;
  }
  public function validCheck($field, $val, $data, $isUpdate) {
    if(!array_key_exists($field, $this->_validation))
      return;
    foreach($this->_validation[$field] as $vld) {
      $msg = $vld['msg'];
      $error = false;
      foreach($vld['valids'] as $k=>$v) {
        $v = $v($data);
        $msg = preg_replace("/\{{$k}-(\d+)\}/i", "E", $msg);
        if(!$v($val)) {
          $error = true;
        }
      }
      if($error)
        return $msg;
    }
  }

  public function primary($field=null, $alias = null) {
    if(!func_num_args()){
      return $this->_fields[$this->_pri_key];
    }

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
  public function rel($table) {
    if(!array_key_exists($table, $this->_has)) {
      trigger_error(sprintf('Undefined relationship "" for "%s"', $table, $this->_name));
    }
    return $this->_has[$table];
  }

  public function hasOne($sk, $table, $fk) {
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
  public function hasMany($sk, $table, $fk, $msk=null, $mfk=null, $mapTable=null) {
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
  public static function defined($name) {
    return array_key_exists($name, static::$_tables)
      || array_key_exists($name, static::$_aliases);
  }
  public static function redefine($name, $alias=null) {
    if(!array_key_exists($name, static::$_tables))
      unset(static::$_tables);
    return static::define($name, $alias);
  } 
  public static function define($name, $alias=null) {
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
  public static function factory($name) {
    $class = get_called_class();
    if($name instanceof $class)
      return $name;
    if(array_key_exists($name, static::$_tables)){
      return static::$_tables[$name];
    }
    if(array_key_exists($name, static::$_aliases)){
      return static::$_aliases[$name];
    }
    trigger_error('Table is undefined: ' . $name);    
  }
  public static function __callStatic($name, $arguments) {
    return static::factory($name);
  }
  public static function fk_for_table($table) {
    return strtr(static::FK_FMT, array('{tbname}' => $table->_name));
  }

  // SQL generator
  public function columnsWithout($cols='') {
    $_cols = array();
    foreach((array)$cols as $_) {
      if(is_string($_))
        $_ = explode(',', $_);
      $_cols = array_merge($_cols, $_);
    }
    $_arr = array_diff($_cols,$this->_fields);
    $_cols = array_diff($this->_fields, $_cols);
    foreach ($_arr as $col) {
      if(array_key_exists($col, $_cols))
        unset($_cols[$col]);
    }
    return $_cols;
  }
  public function columns($cols='*', $whichTable=null, $whichDB=null) {
    // in this table
    $whichTable === true && $whichTable = $this->_name;

    // get selected cols and parse
    $tmp = array();
    foreach((array)$cols as $_) {
      if(is_string($_)) {
        $tmp = array_merge($tmp, explode(',', $_));
      }
      else
        $tmp[] = $_;
    }
    $cols = $tmp;

    // array unique for leaving only 1 '*'
    $cols = array_unique($cols, SORT_REGULAR);
    if(in_array('*', $cols)) {
      array_splice($cols, array_search('*', $cols), 1, array_values($this->_fields));
      $cols = array_unique($cols, SORT_REGULAR);
    }

    // define return NJExpr
    $argsForNJExpr = array();

    // format cols
    $formattedCols = array();
    $flipFields = array_flip($this->_fields);
    foreach($cols as $col) {
      $alias = null;
      if(is_array($col)) {
        list($col, $alias) = $col;
      }

      // is NJExpr
      if($col instanceof NJExpr) {
        $argsForNJExpr = array_merge($argsForNJExpr, $col->parameters());
        $col = $col->stringify();
        if($alias) {
          $col .= ' '.NJMisc::wrapGraveAccent($alias);
        }
      }

      // not NJExpr
      else {
        if(array_key_exists($col, $this->_fields)) {
          $col = NJMisc::wrapGraveAccent($col);
        }
        elseif(array_key_exists($col, $flipFields)) {
          $col = NJMisc::wrapGraveAccent($flipFields[$col])
            .' '.NJMisc::wrapGraveAccent($alias?$alias:$col);
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
      }

      $formattedCols[] = $col;
    }

    array_unshift($argsForNJExpr, implode(',', $formattedCols));
    return (new NJExpr)->parse($argsForNJExpr);
  }

  public function values($values, $update=false) {
    // update
    if($update) {
      return $this->values4update($values);
    }

    // insert
    foreach((array)$this->primary() as $key){
      if(array_key_exists($key, $values))
        unset($values[$key]);
    }
    return $this->values4insert($values);
  }

  protected function values4update($values) {
    if(is_array(current($values))) {
      trigger_error('Update values expect scalars!');
    }
    $argsForNJExpr = array();

    $flipFields = array_flip($this->_fields);
    $tmpArr = array();
    foreach($values as $col => $v) {
      if(array_key_exists($col, $flipFields)) {
        $col = $flipFields[$col];
      }
      elseif(!array_key_exists($col, $this->_fields)) {
        continue;
      }
      // skip validate when it is NJExpr instance
      if($v instanceof NJExpr) {
        $argsForNJExpr = array_merge($argsForNJExpr, $v->parameters());
      }
      else {
        if($ret = $this->validCheck($col, $v, $values, true)) {
          // throw new NJException
          trigger_error($ret);
        }
      }
      $tmpArr[] = NJMisc::wrapGraveAccent($col).'='.NJMisc::formatValue($v);
    }

    array_unshift($argsForNJExpr, implode(',', $tmpArr));
    return (new NJExpr)->parse($argsForNJExpr);
  }

  protected function values4insert($values) {
    // translate one record to multiple records
    if(!is_array(current($values))) {
      return $this->values4insert(array($values));
    }

    $inputKeys = array();
    foreach ($values as $val) {
      $inputKeys = array_merge($inputKeys, array_keys($val));
    }
    $flipFields = array_flip($this->_fields);

    // unique fields and remove that field not in table fields or field aliases
    $fields = array();
    foreach (array_unique($inputKeys) as $col) {
      if(array_key_exists($col, $flipFields))
        $fields[] = $flipFields[$col];
      elseif(array_key_exists($col, $this->_fields)) {
        $fields[] = $col;
      }
    }
    $engraved_fields = array_map(array('\NJORM\NJMisc','wrapGraveAccent'), $fields);

    $argsForNJExpr = array();

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
        if($v instanceof NJExpr) {
          $argsForNJExpr = array_merge($argsForNJExpr, $v->parameters());
        }
        else{
          if($ret = $this->validCheck($field, $v, $val, true)) {
            // throw new NJException
            trigger_error($ret);
          }
        }
        $tmpArr[] = NJMisc::formatValue($v);
      }
      $fmted_vals[] = '('.implode(',', $tmpArr).')';
    }

    $sql = sprintf('(%s) VALUES %s'
      , implode(',', $engraved_fields)
      , implode(',', $fmted_vals));

    array_unshift($argsForNJExpr, $sql);
    return (new NJExpr)->parse($argsForNJExpr);
  }
}