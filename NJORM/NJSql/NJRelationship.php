<?php
/**
 * @Author: byamin
 * @Date:   2015-02-04 23:01:49
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-30 17:18:04
 */
namespace NJORM\NJSql;

class NJRelationship {
  const FK_FMT = 'id_{tbname}';

  public static function oneOne($tcol_1, $tcol_2) {
    $args = array($tcol_1, $tcol_2);

    $args = array_map(function($col){
      $col = explode('.', $col);
      if(count($col) < 2) {
        $t =& $col[0];
        $col[1] = NJTable::$t()->primary();
        if(is_array($col[1])) {
          trigger_error('many-many relationship only support 1 primary current.');
        }
      }
      return $col;
    }, $args);

    list(list($t1, $k1),list($t2, $k2)) = $args;

    NJTable::$t1()->hasOne($k1, $t2, $k2);
    NJTable::$t2()->hasOne($k2, $t1, $k1);
  }
  public static function oneMany($tcol_1, $tcol_2) {
    $args = array($tcol_1, $tcol_2);

    $tbname = null;
    $args = array_map(function($col) use(&$tbname) {
      $col = explode('.', $col);
      if(count($col) < 2) {
        $t = $col[0];
        if($tbname) {
          $col[1] = NJTable::$t()->alias(str_replace('{tbname}', $tbname, static::FK_FMT));
        }
        else {
          $col[1] = NJTable::$t()->primary();
        }
      }

      if(!$tbname)
        $tbname = $col[0];

      return $col;
    }, $args);

    list(list($t1, $k1),list($t2, $k2)) = $args;

    NJTable::$t1()->hasMany($k1, $t2, $k2);
    NJTable::$t2()->hasOne($k2, $t1, $k1);
  }
  public static function manyMany($tcol_1, $tcol_2, $map = null) {
    $args = array($tcol_1, $tcol_2);

    $args = array_map(function($col){
      $col = explode('.', $col);
      if(count($col) < 2) {
        $t =& $col[0];
        $col[1] = NJTable::$t()->primary();
        if(is_array($col[1])) {
          trigger_error('many-many relationship only support 1 primary current.');
        }
      }
      return $col;
    }, $args);

    if(is_string($map)) {
      $map = array($map);
    }
    if(empty($map[0])) {
      $map[0] = implode('', array($args[0][0], $args[1][0]));
      $tbname = implode('_', array($args[0][0], $args[1][0]));
    }
    else {
      $tbname = $map[0];
    }

    if(!NJTable::defined($tbname)) {
      $tb = NJTable::define($tbname, $map[0]);
      $args = array_map(function($col, $idx) use (&$map, &$tb) {
        $t = $col[0];
        $alias =& $map[$idx+1];
        $field = str_replace('{tbname}', $t, static::FK_FMT);
        empty($alias) && $alias = substr($t, 0, 1) .'id';
        $tb->primary($field, $alias);
        return $col;
      }, $args, array_keys($args));
    }

    if(empty($map[1])) {
      $map[1] = NJTable::$tbname()->alias(str_replace('{tbname}', $args[0][0], static::FK_FMT));
    }

    if(empty($map[2])) {
      $map[2] = NJTable::$tbname()->alias(str_replace('{tbname}', $args[1][0], static::FK_FMT));
    }

    list(list($t1, $k1),list($t2, $k2)) = $args;
    list($table, $mk1, $mk2) = $map;

    NJTable::$t1()->hasMany($k1, $t2, $k2, array($table,$mk1,$mk2));
    NJTable::$t2()->hasMany($k2, $t1, $k1, array($table,$mk2,$mk1));
  }
}