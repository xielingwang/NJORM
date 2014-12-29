<?php

namespace NJORM\NJCom;
use \NJORM\NJMisc;
class NJField {
  protected $_name;
  protected $_type;
  protected $_type_arg;
  protected $_notnull;
  protected $_auto_increment;
  protected $_default;
  protected $_comment;

  public function __toString() {
    try {
      return $this->toString();
    }
    catch(\Exception $e) {
      die('NJField error: ' . $e->getMessage());
    }
  }

  public function toString() {
    $this->type_parse();

    if(!$this->_name)
      throw new \Exception('name of field is needed!');

    if(!$this->_type)
      throw new \Exception('type of field is needed!');

    if($this->_default && $this->_auto_increment) {
      throw new \Exception('`default` and `auto_increment` are mutual!');
    }

    $string = sprintf('`%s` %s', $this->_name, $this->_type);
    if($this->_notnull)
      $string .= " NOT NULL";
    if(!is_null($this->_default))
      $string .= " DEFAULT " . NJMisc::value_standardize($this->_default);
    if($this->_comment)
      $string .= " COMMENT '" . addslashes($this->_comment) . "'";
    return $string;
  }

  public function __get($key) {
    if(in_array($key, array('type', 'not_null','name', 'defval', 'comment', 'auto_increment'))) {
      return $this->$key();
    }
  }

  public function auto_increment() {
    $this->_auto_increment = true;
    return $this;
  }

  public function type($type) {
    $this->_type_arg = func_get_args();
    return $this;
  }

  protected function type_parse() {
    return $this->_type = call_user_func_array(array(self, 'format_type'), array_merge($this->_type_arg, array($this->_auto_increment)));
  }

  public function name($name) {
    if(!is_string($name))
      throw new Exception('name is not a string.');
    $this->_name = $name;
    return $this;
  }

  public function not_null() {
    $this->_notnull = true;
    return $this;
  }

  public function defval($default) {
    if(!is_scalar($default))
      throw new Exception('default is not a scalar.');
    $this->_default = $default;
    return $this;
  }

  public function comment($comment) {
    if(!is_string($comment))
      throw new Exception('comment is not a string.');

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

    $type = strtoupper(trim($type));
    static $types = array(
      'INT' => array('M' => 11, 'unsigned' => null, 'auto_increment' => null),
      'TINYINT' => array('M' => 4, 'unsigned' => null, 'auto_increment' => null),
      'SMALLINT' => array('M' => 5, 'unsigned' => null, 'auto_increment' => null),
      'MEDIUMINT' => array('M' => 9, 'unsigned' => null, 'auto_increment' => null),
      'BIGINT' => array('M' => 20, 'unsigned' => null, 'auto_increment' => null),
      'FLOAT' => array('M' => 24, 'D' => null, 'auto_increment' => null),
      'DOUBLE' => array('M' => 53, 'D' => null, 'auto_increment' => null),
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
        throw new \Exception('unsupport type: ' . $type);
      }

      // no default for VARCHAR CHAR
      $nodefault = in_array($type, array('VARCHAR', 'CHAR'));

      foreach($types[$type] as $r_k => $r_v) {

        // attributes: unsigned
        if(!in_array($r_k, array('M', 'D'))) {
          if(!empty($arg)) {
            $v = array_shift($arg);
            printf('%s %d', $v, $v);
            if($v)
              $attributes[] = $r_k;
          }
          continue;
        }

        // parameter: (M, D)
        // no default && need but no value, throw exception
        if($nodefault && $r_v && empty($arg)) {
          throw new \Exception(sprintf('argument "%s" is needed for type: %s', $r_k, $type));
        }

        if(empty($arg)) { continue; }

        do {
          $v = array_shift($arg);

          // null or false
          if($r_v === null || $r_v === false) {
            break;
          }

          // YEAR [2,4]
          if(is_array($r_v)) {
            if(!in_array($v, $r_v)) {
              throw new \Exception(sprintf('"%s" of "%s" must be one of ', $type, $r_k, implode(',', $r_v)));
            }
            break;
          }

          // INT(11)
          if($v > $r_v) {
            throw new \Exception(sprintf('"%s" of "%s" is up to %s', $type, $r_k, $r_v));
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