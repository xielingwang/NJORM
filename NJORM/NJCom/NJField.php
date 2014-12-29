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

  public function __toString() {
    return $this->toString();
  }

  public function toString() {
    if(!$this->_name) {
      trigger_error('name of field should not be null.');
    }

    if(!($this->_type_arg && $this->type_parse())) {
      trigger_error('type of field should not be null.');
    }

    $string = sprintf('`%s` %s', $this->_name, $this->_type);

    if($this->_notnull)
      $string .= " NOT NULL";

    if(!is_null($this->_defined_default))
      $string .= " DEFAULT " . NJMisc::value_standardize($this->_default);

    if($this->_comment)
      $string .= sprintf(" COMMENT '%s'", addslashes($this->_comment));

    return $string;
  }

  public function __get($key) {
    if(in_array($key, array('type', 'not_null','name', 'default', 'comment'))) {
      if($key != 'not_null')
        $key = 'set'.ucfirst($key);
      else
        $key = 'setNotNull';
      return $this->$key();
    }
  }

  public function setType($type) {
    $this->_type_arg = func_get_args();
    return $this;
  }

  protected function type_parse() {
    return $this->_type = call_user_func_array('self::format_type', $this->_type_arg);
  }

  public function setName($name) {
    if(!is_string($name)){
      trigger_error('table name expects a string!', E_USER_ERROR);
    }
    $this->_name = $name;
    return $this;
  }

  public function setNotNull() {
    $this->_notnull = true;
    return $this;
  }

  public function setDefault($default) {
    if(!is_scalar($default)){
      trigger_error('default value expects a scalar!', E_USER_ERROR);
    }

    $this->_default = $default;
    return $this;
  }

  public function setComment($comment) {
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
          if(!empty($arg)) {
            $v = array_shift($arg);
            printf('%s %d', $v, $v);
            if($v)
              $attributes[] = $p_key;
          }
          continue;
        }

        // parameter: (M, D)
        // no default && need but no value, throw exception
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