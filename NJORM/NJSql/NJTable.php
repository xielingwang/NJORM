<?php
/**
 * @Author: byamin
 * @Date:   2015-02-02 23:27:30
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-27 22:11:03
 */

namespace NJORM\NJSql;
use NJORM\NJMisc;
use NJORM\NJValid\NJDuang;

class NJTable {
  protected $_name;
  protected $_alias;
  protected $_pri_key;
  protected $_fields;

  const FK_FMT = 'id_{tbname}';

  public function __construct($name) {
    $this->_name = $name;

    // init NJDuang
    $this->_alias = (func_num_args() > 1) ? func_get_arg(1) : $name;
    $this->_duang = new NJDuang($this->_alias);
  }

  public function __get($name) {
    if(in_array($name, $this->_fields) || array_key_exists($name, $this->_fields))
      return $this->columns($name);
    trigger_error('access undefined attribute "'.$name.'"');
  }

  public function name($alias = null) {
    if($alias) {
      return sprintf("`%s` `%s`", $this->_name, $alias);
    }

    return sprintf("`%s`", $this->_name);
  }

  public function getName() {
    return $this->_name;
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

    if(in_array($alias, $this->_fields))
      trigger_error('field alias existed');

    // set alias with field name
    $alias or $alias = $field;

    // for static::valid()
    $this->_prev_field = $alias;

    $this->_fields[$field] = $alias;
    return $this;
  }

  /**
   * [fields description]
   * @return [type] [description]
   */
  public function fields() {
    if(func_num_args() <= 0)
      trigger_error('NJTable::fields() expects at least 1 parameter.');

    /*
     * Case 1: $this->fields('prefix', array('a', 'b', 'd'), array('a'))
     * translate to:
     * $this->primary('prefix_a', 'a');
     * $this->field('prefix_b', 'b');
     * $this->field('prefix_d', 'd');
     */
    if(func_num_args() > 2) {
      if(!is_string(func_get_arg(0)) || !is_array(func_get_arg(1)))
        trigger_error('NJTable::fields($prefix, $fields, $prikey) expects 1st parameter of string and 2nd parameters of array.');
      $prikey = (array)func_get_arg(2);
      $prefix = func_get_arg(0);
      $fields = func_get_arg(1);

      foreach ($fields as $alias) {
        in_array($alias, $prikey)
        ? $this->primary($prefix.'_'.$alias, $alias)
        : $this->field($prefix.'_'.$alias, $alias);
      }
    }

    /*
     *  Case 2: $this->fields(array('oa' => 'a', 'zb' => 'b', 'cd' => 'd'), array('a'));
     *  truanslate to:
     *  $this->primary('oa', 'a');
     *  $this->primary('zb', 'b');
     *  $this->primary('cd', 'd');
     */
    else {
      if(!is_array(func_get_arg(0)))
        trigger_error('NJTable::fields($fields, $prikey) expects an array');
      $prikey = (array)func_get_arg(1);
      foreach(func_get_arg(0) as $k => $alias) {
        in_array($alias, $prikey)
        ? $this->primary($k, $alias)
        : $this->field($k, $alias);
      }
    }

    return $this;
  }

  private $_prev_field;
  protected $_duang;
  protected $_unique_cols = array();
  public function valid() {
    if(!$this->_prev_field) {
      trigger_error('Field should be define first!');
    }

    $args = func_get_args();
    array_unshift($args, $this->_prev_field);

    return call_user_func_array(array($this, 'validfield'), $args);
  }

  protected function validfield($field) {
    if(!in_array($field, $this->_fields) && array_key_exists($field, $this->_fields)) {
      $field = $this->_fields[$field];
    }

    if(!in_array($field, $this->_fields)) 
      trigger_error("table field '{$field}' not found");

    $args = func_get_args();
    array_shift($args);

    // unique keys
    if(($index = array_search('unique', $args)) !== false){
      $this->_unique_cols[] =$this->_prev_field;
      unset($args[$index]);
    }
    if(($index = array_search(array('unique'), $args)) !== false) {
      $this->_unique_cols[] =$this->_prev_field;
      unset($args[$index]);
    }
    $this->_unique_cols = array_unique($this->_unique_cols);

    // use NJDuang::add() api
    array_unshift($args, $this->_prev_field);
    call_user_func_array(array($this->_duang, 'add'), $args);

    return $this;
  }

  public function unique() {
    if(!$this->_prev_field) {
      trigger_error('Field should be define first!');
    }

    $this->_unique_fields[] = $this->_prev_field;
    $this->_unique_fields = array_filter($this->_unique_fields);

    return $this;
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

  public function alias($field) {
    if(in_array($field, $this->_fields)) {
      return $field;
    }
    if(array_key_exists($field, $this->_fields)) {
      return $this->_fields[$field];
    }
    trigger_error("Field `{$this->_name}`.`{$field}` is not found.");
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
  public $_has = array();
  public function rel($table) {
    if(!array_key_exists($table, $this->_has)) {
      trigger_error(sprintf('Undefined relationship "" for "%s"', $table, $this->_name));
    }
    return $this->_has[$table];
  }

  public function hasOne($sk, $table, $fk) {

    $this->_has[$table] = array(
      'type' => static::TYPE_RELATION_ONE,
      'fk' => $fk,
      'sk' => $sk,
      );

    return $this;
  }
  public function hasMany($sk, $table, $fk) {
    $fk = static::get_real_field(static::$table(), $fk, $this);

    $this->_has[$table] = array(
      'type' => static::TYPE_RELATION_MANY,
      'fk' => $fk,
      'sk' => $sk,
      );

    return $this;
  }
  public function hasManyX($table, $mapTable, $smap, $fmap){

    $this->_has[$table] = array(
      'type' => static::TYPE_RELATION_MANY_X,
      'table' => $mapTable,
      'smap' => $smap,
      'fmap' => $fmap,
      );

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
    unset(static::$_tables[$name]);
    unset(static::$_tables[$alias]);
    return static::define($name, $alias);
  } 
  public static function define($name, $alias=null) {
    if(!array_key_exists($name, static::$_tables)) {
      static::$_tables[$name] = new static($name, $alias);
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
      unset($_cols[$col]);
    }
    return $_cols;
  }

  public function columns($cols='*', $whichTable=null, $whichDB=null) {
    $ClassNJExpr = __NAMESPACE__.'\NJExpr';
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
      if($col instanceof $ClassNJExpr) {
        $alias || $alias=$col->as();

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
      // var_dump($argsForNJExpr, $formattedCols);die;

    array_unshift($argsForNJExpr, implode(',', $formattedCols));
    return (new NJExpr)->parse($argsForNJExpr);
  }

  protected function filterValues($values) {

    // filter: remove unavailable cols
    $refFields =& $this->_fields;
    $flipFields = array_flip($refFields);
    $keyvalues = array_map(function($col, $val) use ($refFields, $flipFields) {
      if(in_array($col, $refFields))
        return array($col => $val);

      if(in_array($col, $flipFields))
        return array($refFields[$col] => $val);

    }, array_keys($values), array_values($values));
    if(!$keyvalues)
      return array();
    return call_user_func_array('array_merge', array_filter($keyvalues));
  }

  protected function executeDuang($values, $update) {
    $duang = clone $this->_duang;

    if(true or $this->_unique_cols) {
      $table = $this->_alias;
      $extra = null;
      if($update && array_key_exists($this->_pri_key, $values)) {
        $extra = array($this->_pri_key, '!=', $values[$this->_pri_key]);
      }

      array_map(function($col) use (&$duang, $table, $extra) {
        $duang->add($col, ['unique', $col, $table, $extra]);
      }, $this->_unique_cols);
    }

    $duang($values, $update);
  }

  public function values($values, $update=false) {
    if($update) {
      // execute Duang
      $this->executeDuang($values, $update);

      // filter: remove primary key values
      foreach((array)$this->primary() as $key) {
        unset($values[$key]);
      }

      return $this->values4update($values);
    }
    else {
      // remove unavailable cols and duang
      $_values = array();
      foreach ($values as $vals) {
        $vals = $this->filterValues($vals);

        // execute Duang
        $this->executeDuang($vals, $update);

        $_values[] = $vals;
      }

      return $this->values4insert($_values);
    }
  }

  protected function values4update($values) {
    if(is_array(current($values))) {
      trigger_error('Update values expect scalars!');
    }

    // flipFields
    $flipFields = array_flip($this->_fields);

    $params = array();
    $exprs = array_map(function($col, $v) use(&$params, $flipFields) {
      static $c; $c or $c = __NAMESPACE__.'\\NJExpr';
      if($v instanceof $c) {
        $params[] = $v->parameters();
      }
      return NJMisc::wrapGraveAccent($flipFields[$col]).'='.NJMisc::formatValue($v);
    }
    , array_keys($values)
    , array_values($values));

    // $params = merge($params[0], $params[1], $params[2], ...)
    $params && $params = call_user_func_array('array_merge', $params);

    array_unshift($params, implode(',', $exprs));
    return (new NJExpr)->parse($params);
  }

  protected function values4insert($values) {
    // 1. get all cols and merge then unique them
    $cols = array_unique(call_user_func_array('array_merge', array_map(function($_){ return array_keys($_); }, $values)));

    // 2.dbrow-values
    $flipFields = array_flip($this->_fields);
    $params = array();
    $dbrows = array_map(function($vals) use ($cols, $flipFields, &$params){
      $dbvals = array_map(function($col) use($vals, $flipFields, &$params) {
        static $c; $c or $c = __NAMESPACE__.'\\NJExpr';

        $v = array_key_exists($col, $vals) ? $vals[$col] : null;
        if($v instanceof $c)
          $params[] = $v->parameters();

        return NJMisc::formatValue($v);
      }, $cols);

      return '('.implode(',', $dbvals).')';
    }, $values);

    // $params = merge($params[0], $params[1], $params[2], ...)
    $params && $params = call_user_func_array('array_merge', $params);

    // 3.dbcols
    $dbcols = array_map(function($col) use ($flipFields) {
      return \NJORM\NJMisc::wrapGraveAccent($flipFields[$col]);
    }, $cols);

    // 4.NJExpr
    $sql = sprintf('(%s) VALUES %s'
      , implode(',', $dbcols)
      , implode(',', $dbrows));

    // 5. return 
    array_unshift($params, $sql);
    return (new NJExpr)->parse($params);
  }
}