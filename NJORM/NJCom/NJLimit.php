<?php
/**
 * @Author: byamin
 * @Date:   2014-12-26 01:41:57
 * @Last Modified by:   byamin
 * @Last Modified time: 2014-12-26 02:04:07
 */
namespace NJORM\NJCom;
class NJLimit {
  const TYPE_COMMA = true;
  const TYPE_OFFSET = false;

  protected $_limit;
  protected $_offset;
  protected $_type = TYPE_COMMA;

  public function __construct() {
    if(func_num_args() > 0) {
      call_user_func_array(array($this, 'limit'), func_get_args());
    }
  }

  public function limit() {
    if(func_num_args() < 1)
      throw new Exception("limit at least a parameter.");
    if(func_num_args() > 1) {
      $this->_type = self::TYPE_COMMA;
      $this->_offset = intval(func_get_arg(0));
      $this->_limit = intval(func_get_arg(1));
    }
    else
      $this->_limit = intval(func_get_arg(0));
    return $this;
  }

  public function offset($offset) {
    $this->_type = self::TYPE_OFFSET;
    $this->_offset = intval($offset);
    return $this;
  }

  public function __toString() {
    if(is_null($this->_limit)) 
      throw new Exception("limit parameter is needed");

    if(self::TYPE_OFFSET == $this->_type) {
      $str = "LIMIT " . $this->_limit;
      if($this->_offset){
        $str .= " OFFSET " . $this->_offset;
      }
    }
    else {
      $str = "LIMIT ";
      if($this->_offset){
        $str .= $this->_offset . ",";
      }
      $str .= $this->_limit;
    }
    return $str;
  }
}