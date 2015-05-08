<?php
/**
* @Author: byamin
* @Date:   2014-12-27 00:34:13
* @Last Modified by:   byamin
* @Last Modified time: 2014-12-27 09:58:45
*/
namespace NJORM;
use NJORM\NJSql\NJExprInterface;
use NJORM\NJSql\NJExpr;
class NJMisc {
/**
 * [wrapGraveAccent description]
 * @param  [type] $v [description]
 * @return [type]    [description]
 */
public static function wrapGraveAccent($v) {
  $v = trim($v);

  if(!NJORM::isDriver('mysql'))
    return $v;

  if(!is_numeric($v) && !self::isWrappedGraveAccent($v)) {
    $v = "`{$v}`";
  }
  return $v;
}
/**
 * [unwrapGraveAccent description]
 * @param  [type] $v [description]
 * @return [type]    [description]
 */
public static function unwrapGraveAccent($v) {
  $v = trim($v);
  if(self::isWrappedGraveAccent($v)) {
    $v = substr($v, 1, strlen($v)-2);
  }
  return $v;
}

/**
 * [formatValue description]
 * @param  [type] $v [description]
 * @return [type]    [description]
 */
public static function formatValue($v, $context=null) {

  // NJExpr
  if($v instanceof NJExprInterface) {
    if($context instanceof NJExpr) {
      $context->addParameters($v->parameters());
    }
    $str = $v->stringify();
    if($v->isEnclosed()) {
      $str = '('.$str.')';
    }
    return $str;
  }

  // array
  if(is_array($v)) {
    if(empty($v)) {
      return 'NULL';
    }

    foreach($v as &$_v) {
      $_v = NJMisc::formatValue($_v);
    }
    return '(' . implode(',', $v) . ')';
  }

  // numeric
  elseif(is_int($v) || is_float($v) || $v === '?') {
    return $v;
  }

  // null
  elseif(is_null($v)) {
    return 'NULL';
  }

  // boolean
  elseif(is_bool($v)) {
    return $v ? 'TRUE' : 'FALSE';
  }

  // string
  elseif(is_string($v)) {
    if(static::isWrappedGraveAccent($v))
      return $v;
    return '\''.str_replace('\'','\'\'',$v).'\'';
  }

  // how to support object/resource
  else {
    trigger_error('Unexpected type for formatValue(): ' . $v . " " . gettype($v), E_USER_ERROR);
  }

  return $v;
}

/**
 * [formatOperator description]
 * @param  [type] $op [description]
 * @return [type]     [description]
 */
public static function formatOperator($op, $val) {
  $op = strtoupper(preg_replace('/\s+/i', ' ', trim($op)));
  if(!self::isOperatorSupported($op)){
    trigger_error("illegal operator " . $op, E_USER_ERROR);
  }

  is_array($val) && empty($val) && $val = null;

  // IN IS
  if(in_array($op, array('=','=='))){
    if(is_array($val) || ($val instanceof NJExprInterface)){
      $op = 'IN';
    }
    elseif(is_bool($val) || is_null($val)) {
      return 'IS';
    }
  }

  // NOT IN / IS NOT
  elseif(in_array($op, array('!=','<>'))){
    if(is_array($val) || ($val instanceof NJExprInterface)){
      $op = 'NOT IN';
    }
    elseif(is_bool($val) || is_null($val)) {
      return 'IS NOT';
    }
  }

  return $op;
}

/**
 * [isWrappedGraveAccent description]
 * @param  [type]  $v [description]
 * @return boolean    [description]
 */
public static function isWrappedGraveAccent($v) {

  $v = trim($v);
  return strlen($v)>=2
    && substr($v, 0, 1) == '`'
    && substr($v, -1) == '`';
}

/**
 * [formatFieldName description]
 * @param  [type] $arg [description]
 * @return [type]      [description]
 */
public static function formatFieldName($arg) {
  $arg = func_get_args();

  $ret = array();
  foreach($arg as $_) {
    if(!is_array($_)){
      $_ = explode('.', $_);
    }
    $ret = array_merge($ret, $_);
  }

  $ret = array_map(function($v) {
    return NJMisc::wrapGraveAccent($v);
  }, $ret);

  return implode('.', $ret);
}

/**
 * [nullOperator description]
 * @param  [type] $op [description]
 * @return [type]     [description]
 */
public static function nullOperator($op) {
  if(in_array($op, array('==','='))) {
    $op = 'IS';
  }
  elseif(in_array($op, array('!=', '<>'))) {
    $op = 'IS NOT';
  }
  return $op;
}

/**
 * [arrayOperator description]
 * @param  [type] $op [description]
 * @return [type]     [description]
 */
public static function arrayOperator($operator) {
  if($operator == '=') {
    $operator = 'IN';
  }

  elseif(in_array($operator, array('!=', '<>'))) {
    $operator = 'NOT IN';
  }

  return $operator;
}

public static function supportedOperators($joins=null) {
  static $operators;
  if(!$operators) {
    $operators = array_merge(static::specialOperators(), static::normalOperators());
  }
  if(!is_null($joins)) {
    return implode($joins, $operators);
  }
  return $operators;
}

public static function specialOperators($joins=null) {
  static $operators = array('IN','NOT IN','BETWEEN','NOT BETWEEN');
  if(!is_null($joins)) {
    return implode($joins, $operators);
  }
  return $operators;
}
public static function normalOperators($joins=null) {
  static $operators = array('=','>=','>','<=','<','<>','!=','<=>','IS','IS NOT','REGEXP', 'NOT REGEXP','LIKE','NOT LIKE');
  if(!is_null($joins)) {
    return implode($joins, $operators);
  }
  return $operators;
}

/**
 * [isOperatorSupported description]
 * @param  [type]  $operator [description]
 * @return boolean           [description]
 */
public static function isOperatorSupported($operator) {
  return in_array($operator, static::supportedOperators());
}

}