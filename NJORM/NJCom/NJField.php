<?php

namespace NJORM\NJCom;
class NJField {
  protected $_name = 'pk1';
  protected $_type = 'INT(11) unsigned';
  protected $_notnull;
  protected $_default;
  protected $_comment;

  public function __construct($name, $type) {

    $this->name = $name;
    $this->type = $type;

    if(func_num_args() > 3) {
      if(is_bool(func_get_arg(3))) {
        $this->notnull = func_get_arg(3);
        if(func_num_args()>4)
          $this->default = func_get_arg(4);
        if(func_num_args()>5)
          $this->comment = func_get_arg(5);
      }
      else {
        $this->comment = func_get_arg(3);
      }
    }
    return $this;
  }
  /**
   * [format_type description]
   * @param  [type] $type [description]
   * @return [type]       [description]
   */
  public static function format_type($type) {
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

    $params = array();
    $attributes = array();

    $arg = func_get_args();
    array_shift($arg);

    do {
      // type restrict
      if(array_key_exists($type, $types)){
        if(empty($types[$type]))
          break;

        // can be ignore for numberic and date time types
        $nodefault = in_array($type, array('VARCHAR', 'CHAR'));
        foreach($types[$type] as $r_k => $r_v) {

          if(in_array($r_k, array('M', 'D'))) {

            // if restric needed but empty
            if($nodefault && $r_v && empty($arg)) {
              throw new \Exception(sprintf('argument "%s" is needed for type: %s', $r_k, $type));
            }
            if(!empty($arg)) {

              // if restric not true
              $v = array_shift($arg);
              if($r_v !== null) {
                if(!is_array($r_v)) {
                  if($v > $r_v) {
                    throw new \Exception(sprintf('"%s" of "%s" is up to %s', $type, $r_k, $r_v));
                  }
                }
                else {
                  if(!in_array($v, $r_v)) {
                    throw new \Exception(sprintf('"%s" of "%s" must be one of ', $type, $r_k, implode(',', $r_v)));
                  }
                }
              }
              $params[] = $v;
            }
          }

          else {
            if(!empty($arg)) {
              if(array_shift($arg)){
                $attributes[] = $r_k;
              }
            }
          }
        }
        break;
      }
    }
    while(0);

    // return
    $str = $type;
    if(!empty($params)) {
      $str .= '(' . implode(',', $params) . ')';
    }
    if(!empty($attributes)) {
      $str .= ' ' . implode(' ', $attributes);
    }
    return $str;
  }
}