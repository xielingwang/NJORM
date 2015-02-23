<?php
/**
* @Author: byamin
* @Date:   2014-12-27 00:34:13
* @Last Modified by:   byamin
* @Last Modified time: 2014-12-27 09:58:45
*/
namespace NJORM;
use NJORM\NJSql\NJObject;
class NJMisc{
/**
 * [wrap_grave_accent description]
 * @param  [type] $v [description]
 * @return [type]    [description]
 */
public static function wrap_grave_accent($v) {
  $v = trim($v);
  if(!self::is_wrap_grave_accent($v))
    $v = "`{$v}`";
  return $v;
}

/**
 * [formatValue description]
 * @param  [type] $v [description]
 * @return [type]    [description]
 */
public static function formatValue($v, $context=null) {

  // NJObject
  if($v instanceof NJObject) {
    if($context instanceof NJObject) {
      $context->addParameters($v->parameters());
    }
    return $v->stringify();
  }

  // array
  if(is_array($v)) {
    if(empty($v)) {
      trigger_error('array value for formatValue() cant be empty!', E_USER_ERROR);
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

  // string
  elseif(is_string($v)) {
    if(strpos($v, '`') !== false)
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
public static function formatOperator($op) {
  $op = strtoupper(preg_replace('/\s+/i', ' ', trim($op)));
  if(!self::isOperatorSupported($op)){
    trigger_error("illegal operator " . $op, E_USER_ERROR);
  }
  return $op;
}

/**
 * [is_wrap_grave_accent description]
 * @param  [type]  $v [description]
 * @return boolean    [description]
 */
public static function is_wrap_grave_accent($v) {
  $v = trim($v);
  return strpos($v, '`') === 0 && strpos(strrev($v), '`') === 0;
}

/**
 * [formatFieldName description]
 * @param  [type] $arg [description]
 * @return [type]      [description]
 */
public static function formatFieldName($arg) {
  if(func_num_args() > 1) {
    $arg = func_get_args();
  }

  if(!is_array($arg)){
    $arg = explode('.', $arg);
  }

  foreach($arg as &$v) {
    $v = self::wrap_grave_accent($v);
  }

  return implode('.', $arg);
}

/**
 * [operatorForNull description]
 * @param  [type] $op [description]
 * @return [type]     [description]
 */
public static function operatorForNull($op) {
  if(in_array($op, array('==','='))) {
    $op = 'IS';
  }
  elseif(in_array($op, array('!=', '<>'))) {
    $op = 'IS NOT';
  }
  return $op;
}

/**
 * [operatorForArray description]
 * @param  [type] $op [description]
 * @return [type]     [description]
 */
public static function operatorForArray($operator) {
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