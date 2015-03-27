<?php
/**
 * @Author: byamin
 * @Date:   2015-02-04 23:01:49
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-27 22:20:43
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
  public static function manyMany($lnk_1, $lnk_2, $maptable = null) {
    $lnks = array($lnk_1, $lnk_2);

    $lnks = array_map(function($lnk){
      $lnk = array_map(function($str){return trim($str);}, explode('<=>', str_replace(array('<->'), '<=>', $lnk)));
      count($lnk) < 2 && $lnk[1] = null;

      $lnk = array_map(function($col, $key) {
        if($key == 0) {
          list($t) = $col = explode('.', $col);

          if(count($col) < 2) {
            $col[1] = NJTable::$t()->primary();
            if(is_array($col[1])) {
              trigger_error('many-many relationship only support 1 primary current.');
            }
          }
        }
        return $col;
      }, $lnk, array_keys($lnk));

      return $lnk;
    }, $lnks);

    if(!$maptable) {
      $maptable = implode('', array($lnks[0][0][0], $lnks[1][0][0]));
      $maptable_table = implode('_', array($lnks[0][0][0], $lnks[1][0][0]));
      if(!NJTable::defined($maptable_table)) {
        $tb = NJTable::define($maptable_table, $maptable);

        $lnks = array_map(function($lnk) use (&$tb) {
          $t = $lnk[0][0]; 
          $field = str_replace('{tbname}', $t, static::FK_FMT);
          $alias = substr($t, 0, 2) .'id';
          $tb->primary($field, $alias);
          return $lnk;
        }, $lnks);
      }
    }

    $lnks = array_map(function($lnk) use ($maptable) {
      if(!$lnk[1]) {
        $t = $lnk[0][0];
        $field = str_replace('{tbname}', $t, static::FK_FMT);
        $lnk[1] = NJTable::$maptable()->alias($field);
      }
      return $lnk;
    }, $lnks);

    list(list(list($t1, $k1), $mk1), list(list($t2, $k2), $mk2)) = $lnks;

    NJTable::$t1()->hasManyX($t2,$maptable,array($k1,$mk1), array($k2,$mk2));
    NJTable::$t2()->hasManyX($t1,$maptable,array($k2,$mk2), array($k1,$mk1));
  }
}