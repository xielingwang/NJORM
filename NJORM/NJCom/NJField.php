<?php

namespace NJORM\NJCom;
use \NJORM\NJMisc;
class NJField {
  protected $_name;
  protected $_type;
  protected $_type_arg;
  protected $_notnull;

  protected $_default;
  protected $_defined_default = false;

  protected $_comment;

  protected $_auto_increment_msg;
  protected $_auto_increment;

  public function toDefine() {
    if(!$this->_name) {
      trigger_error('name of field should be set.', E_USER_ERROR);
    }

    if(!$this->_type) {
      trigger_error('type of field should be set.', E_USER_ERROR);
    }

    // type
    $stmt = sprintf('%s %s', NJMisc::field_standardize($this->_name), $this->_type);

    // not null
    if($this->_notnull)
      $stmt .= " NOT NULL";

    // default
    if($this->_defined_default)
      $stmt .= " DEFAULT " . NJMisc::value_standardize($this->_default);

    // auto increment
    if($this->_auto_increment) {
      $stmt .= " AUTO_INCREMENT";
    }

    // comment
    if($this->_comment)
      $stmt .= sprintf(" COMMENT '%s'", addslashes($this->_comment));

    return $stmt;
  }

  public function __get($key) {
    if(in_array($key, array('type', 'notnull', 'name', 'default', 'comment'))) {
      $key = '_' . $key;
      return $this->$key;
    }
  }

  public function auto_increment() {
    if($this->_auto_increment_msg){
      trigger_error($this->_auto_increment_msg, E_USER_ERROR);
    }
    $this->_auto_increment = true;
    return $this;
  }

  public function type($type) {
    $this->_auto_increment_msg = null;

    if(stripos('int', $type) === false && stripos('float', $type) === false && stripos('double', $type) === false) {
      $this->_auto_increment_msg = $type . ' can be used with auto_increment.';
    }

    $this->_type_arg = func_get_args();
    $this->_type = call_user_func_array('self::format_type', $this->_type_arg);

    return $this;
  }

  public function name($name) {
    if(!is_string($name)){
      trigger_error('table name expects a string!', E_USER_ERROR);
    }
    $this->_name = $name;
    return $this;
  }

  public function notnull() {
    $this->_notnull = true;
    return $this;
  }

  public function __call($name, $args){
    if($name == 'default'){
      return call_user_func_array(array($this, '_default'), $args);
    }
  }

  protected function _default($default) {
    if(!is_scalar($default)){
      trigger_error('default value expects a scalar!', E_USER_ERROR);
    }

    // 
    $this->_auto_increment_msg = 'default value can be used with auto_increment meantime.';
    $this->_defined_default = true;
    $this->_default = $default;
    return $this;
  }

  public function comment($comment) {
    if(!is_string($comment)) {
      trigger_error('comment value expects a string!', E_USER_ERROR);
    }

    $this->_comment = $comment;
    return $this;
  }

  public function __construct($njtable) {
    $this->_table = $njtable;
  }

  /**
   * [format_type description]
   * @param  [type] $type [description]
   * @return [type]       [description]
   */
  public static function format_type($type) {

    if(!is_string($type)) {
      trigger_error('field type expects a string, '.gettype($type).' is given.', E_USER_ERROR);
    }

    $type = strtoupper(trim($type));
    static $types = array(
      'INT' => array('M' => 11, 'unsigned' => null),
      'TINYINT' => array('M' => 4, 'unsigned' => null),
      'SMALLINT' => array('M' => 5, 'unsigned' => null),
      'MEDIUMINT' => array('M' => 9, 'unsigned' => null),
      'BIGINT' => array('M' => 20, 'unsigned' => null),
      'FLOAT' => array('M' => 24, 'D' => null),
      'DOUBLE' => array('M' => 53, 'D' => null),
      'DECIMAL' => array('M' => true, 'D' => null),
      'DATE' => null,
      'TIME' => null,
      'DATETIME' => null,
      'TIMESTAMP' => null,
      'YEAR' => array('M' => array(2,4)),
      'CHAR' => array('M' => 255),
      'VARCHAR' => array('M' => 255),
      'ENUM' => null,
      'TINYTEXT' => null,
      'TEXT' => null,
      'MEDIUMTEXT' => null,
      'LONGTEXT' => null,
      'TINYBLOB' => null,
      'BLOB' => null,
      'MEDIUMBLOB' => null,
      'LONGBLOB' => null,
    );

    $parameters = array();
    $attributes = array();

    $arg = func_get_args();
    array_shift($arg);

    do {
      // type restrict
      if(!array_key_exists($type, $types)) {
        trigger_error(sprintf('Type "%s" is not a available mysql type.', $type), E_USER_ERROR);
      }

      // no default for VARCHAR CHAR
      $parameter_needed = in_array($type, array('VARCHAR', 'CHAR'));

      // parameter key && parameter value
      foreach($types[$type] as $p_key => $p_val) {

        // attributes: unsigned
        if(!in_array($p_key, array('M', 'D'))) {
          if(!empty($arg) && array_shift($arg)) {
            $attributes[] = $p_key;
          }
          continue;
        }

        // parameter: (M, D)
        // no default && need but no value, trigger error
        if($parameter_needed && $p_val && empty($arg)) {
          trigger_error(sprintf('Type "%s" expects parameter "%s" ', $type, $p_key), E_USER_ERROR);
        }

        if(empty($arg)) { continue; }

        do {
          $v = array_shift($arg);

          // null or false
          if($p_val === null || $p_val === false) {
            break;
          }

          // YEAR [2,4]
          if(is_array($p_val)) {
            if(!in_array($v, $p_val)) {
              trigger_error(sprintf('"%s" for type "%s" should be %s', $p_key, $type, implode(' or ', $p_val)), E_USER_ERROR);
            }
            break;
          }

          // INT(11)
          if($v > $p_val) {
            trigger_error(sprintf('"%s" of type "%s" is up to %s', $type, $p_key, $p_val), E_USER_ERROR);
          }
        } while(0);

        $parameters[] = $v;
      }
    }
    while(0);

    if(!empty($arg)) {

    }

    // return
    $string = $type;
    if(!empty($parameters)) {
      $string .= '('.implode(',', $parameters).')';
    }

    if(!empty($attributes)) {
      $string .= ' '.implode(' ', $attributes);
    }

    return $string;
  }
}