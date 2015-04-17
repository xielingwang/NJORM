<?php
/**
 * @Author: AminBy
 * @Date:   2015-03-24 17:27:30
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-04-16 16:52:53
 */

namespace NJORM;
use \NJORM\NJValid\NJRule;

class NJException extends \Exception {
  const TYPE_DBACCESS = 'error_dbaccess';
  const TYPE_DBEXECUTION = 'error_sql_execution';
  const TYPE_VALIDATION = 'error_validation';

  protected $_messages = [];
  protected $_msgs = [];
  protected $_type;

  const TYPE_SYST = 0;
  const TYPE_USER = 1;
  public function __construct($type, $params = null) {
    $this->_messages = array_merge($this->_messages, NJRule::messages(), static::$s_messages);

    $this->_type = is_null($type) ? static::TYPE_DBACCESS : $type;

    $this->init_error_msgs();
  }

  public function init_error_msgs() {

  }

  protected static $s_messages = array();
  public static function setMessage($key, $message) {
    static::$s_messages[$key] = $message;
  }

  public function isEmpty() {
    return empty($this->_msgs);
  }

  public function getMsgs() {
    return $this->_msgs;
  }

  public function getMsg() {
    return reset($this->_msgs);
  }

  public function getType() {
    return $this->_type;
  }

  public function addValidFailed($domain, $key, $rule, $params, $val) {
    $sep = '/';
    do {
      // D-K-R
      if($domain) {
        if($key && $rule) {
          $mk = implode($sep, array($domain, $key, $rule));
          if(!empty($this->_messages[$mk])) {
            $message = $this->_messages[$mk];
            break;
          }
        }

        // D-K
        if($key) {
          $mk = implode($sep, array($domain, $key));
          if(!empty($this->_messages[$mk])) {
            $message = $this->_messages[$mk];
            break;
          }
        }

        // D-R
        if($rule) {
          $mk = implode($sep, array($domain, $rule));
          if(!empty($this->_messages[$mk])) {
            $message = $this->_messages[$mk];
            break;
          }
        }
      }

      // K-R
      if($key) {
        $mk = implode($sep, array($key, $rule));
        if(!empty($this->_messages[$mk])) {
          $message = $this->_messages[$mk];
          break;
        }
      }

      // R
      $mk =& $rule;
      if(!empty($this->_messages[$mk])) {
        $message = $this->_messages[$mk];
        break;
      }
    }
    while(0);

    if(empty($message)) {
      $msg = implode('-', array_filter(
        array_merge(array($domain, $key, $rule), $params)
        ));
    }
    else {
      $msg = $this->parseMsg($message, $val, $params, $key, $domain);
    }

    $this->_msgs[] = $msg;
  }

  protected function parseMsg($msg, $val, $params, $key, $domain) {
    $toString = function($v) {
      if(is_array($v) || is_object($v))
        return json_encode($v);
      if(is_null($v))
        return 'nil';
      return $v;
    };

    $hasKey = false;
    $ret = preg_replace_callback('/{([a-z])(?:([0-9])+)?}/i', function($match) use ($domain, $key, $val, $params, $toString, $hasKey) {

      // value
      if($match[1] == 'v')
        return $toString($val);
      
      // params
      if( $match[1] == 'p') {
        if(array_key_exists(2, $match)) {
          $offset = $match[2] - 1;
          if(array_key_exists($offset, $params))
            return $toString($params[$offset]);
        }
        else {
          $d = current($params); next($params);
          return $toString($d);
        }
        return $match[0];
      }

      // key
      if( $match[1] == 'k') {
        $hasKey = true;
        return $key;
      }

      // domain
      if( $match[1] == 'd') {
        return $domain;
      }

      // not available holdplacers
      return $match[0];
    }, $msg);

    if($key && !$hasKey) {
      $ret = "Key \"{$key}\": " . $ret;
    }

    return $ret;
  }
}