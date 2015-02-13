<?php
/**
 * @Author: byamin
 * @Date:   2015-02-04 23:01:49
 * @Last Modified by:   Amin by
 * @Last Modified time: 2015-02-13 20:15:56
 */
namespace NJORM\NJSql;

class NJRelationship {
  public static function oneOne($config) {
    list($t1, $t2) = array_keys($config);
    list($k1, $k2) = array_values($config);
    NJTable::$t1()->hasOne($k1, $t2, $k2);
    NJTable::$t2()->hasOne($k2, $t1, $k1);
  }
  public static function oneMany($config) {
    list($t1, $t2) = array_keys($config);
    list($k1, $k2) = array_values($config);
    NJTable::$t1()->hasMany($k1, $t2, $k2);
    NJTable::$t2()->hasOne($k2, $t1, $k1);
  }
  public static function manyMany($config, $mconfig=null, $maptable = null) {
    if(!$mconfig)
      $mconfig = $config;
    list($t1,$t2) = array_keys($config);
    list($k1,$k2) = array_values($config);
    $t1_mk = $mconfig[$t1];
    $t2_mk = $mconfig[$t2];
    $maptable || $maptable = implode('_', array_keys($config));

    // define map table if not exists
    if(!NJTable::defined($maptable)) {
      $tb = NJTable::define($maptable);
      $t1_mk || $t1_mk = NJTable::fk_for_table(NJTable::$t1());
      $t2_mk || $t2_mk = NJTable::fk_for_table(NJTable::$t2());
      if(!$tb->defined_primary($t1_mk)) {
        $tb->primary($t1_mk, $t1_mk);
      }
      if(!$tb->defined_primary($t2_mk)) {
        $tb->primary($t2_mk, $t2_mk);
      }
    }

    NJTable::$t1()->hasMany($k1,$t2,$k2,$t1_mk,$t2_mk,$maptable);
    NJTable::$t2()->hasMany($k2,$t1,$k1,$t2_mk,$t1_mk,$maptable);
  }
}