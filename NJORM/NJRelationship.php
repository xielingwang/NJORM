<?php
/**
 * @Author: byamin
 * @Date:   2015-02-04 23:01:49
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-02-05 08:46:26
 */
namespace NJORM;

class NJRelationship {
  public static function oneOne($t1, $t2, $key_map) {
    NJTable::$t1()->hasOne($t2, array_values($key_map), array_keys($key_map));
    NJTable::$t2()->hasOne($t1, array_keys($key_map), array_values($key_map));
  }
  public static function oneMany($t1, $t2, $key_map) {
    NJTable::$t1()->hasMany($t2, array_values($key_map), array_keys($key_map));
    NJTable::$t2()->hasOne($t1, array_keys($key_map), array_values($key_map));
  }
  public static function manyMany($t1, $t2, $map, $key_map_t1, $key_map_t2) {
    NJTable::$t1()->hasMany($t2
      , array_values($key_map_t2)
      , array_keys($key_map_t1)
      , $map
      , array_values($key_map_t1)
      , array_keys($key_map_t2)
      );
    NJTable::$t2()->hasMany($t1
      , array_values($key_map_t1)
      , array_keys($key_map_t2)
      , $map
      , array_values($key_map_t2)
      , array_keys($key_map_t1)
      );
  }
}