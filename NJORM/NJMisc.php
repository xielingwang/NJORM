<?php
/**
* @Author: byamin
* @Date:   2014-12-27 00:34:13
* @Last Modified by:   byamin
* @Last Modified time: 2014-12-27 09:58:45
*/
namespace NJORM;

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
 * [value_standardize description]
 * @param  [type] $v [description]
 * @return [type]    [description]
 */
public static function value_standardize($v) {

  // is scalar
  if(is_scalar($v)) {
    if(strpos($v, '`') !== false)
      return $v;
    if(!is_numeric($v)) {
      return '\''.addslashes($v).'\'';
    }
  }

  // is array
  elseif(is_array($v)) {
    if(empty($v))
      throw new \Exception('array value is empty!');
    foreach($v as &$_v) {
      $_v = NJMisc::value_standardize($_v);
    }
    return '(' . implode(',', $v) . ')';
  }

  // how to support object/resource
  else {
    throw new \Exception('can not standardize for type ' . gettype($v));
  }

  return $v;
}

/**
 * [op_standardize description]
 * @param  [type] $op [description]
 * @return [type]     [description]
 */
public static function op_standardize($op) {
  $op = strtoupper(preg_replace('/\s+/i', ' ', trim($op)));
  if(!self::op_supported($op)){
    throw new \Exception( "illegal operator " . $op );
  }
  return $op;
}

/**
 * [is_wrap_grave_accent description]
 * @param  [type]  $v [description]
 * @return boolean    [description]
 */
public static function is_wrap_grave_accent($v) {
  return strpos('`', $v) === 0 && strrpos('`', $v) === (strlen($v)-1);
}

/**
 * [field_standardize description]
 * @param  [type] $arg [description]
 * @return [type]      [description]
 */
public static function field_standardize($arg) {
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
 * [equal2is description]
 * @param  [type] $op [description]
 * @return [type]     [description]
 */
public static function equal2is($op) {
  if(in_array($op, array('==','='))) {
    $op = 'IS';
  }
  elseif(in_array($op, array('!=', '<>'))) {
    $op = 'IS NOT';
  }
  return $op;
}

/**
 * [equal2in description]
 * @param  [type] $op [description]
 * @return [type]     [description]
 */
public static function equal2in($op) {
  if(in_array($op, array('==','='))) {
    $op = 'IN';
  }
  elseif(in_array($op, array('!=', '<>'))) {
    $op = 'NOT IN';
  }
  return $op;
}

/**
 * [op_supported description]
 * @param  [type]  $op        [description]
 * @param  boolean $exception [description]
 * @return [type]             [description]
 */
public static function op_supported($op) {
  $ops = array('=','>=','>','<=','<','<>','!=','<=>','IS','IS NOT','IN','NOT IN','BETWEEN','NOT BETWEEN','REGEXP', 'NOT REGEXP','LIKE','NOT LIKE');
  return in_array($op, $ops);
}

}