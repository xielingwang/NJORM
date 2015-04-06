<?php
/**
 * @File: NJPipeline.php
 * @Author: AminBy (xielingwang@gmail.com)
 * @Date:   2015-04-03 23:39:53
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-04-06 22:13:40
 */

namespace NJORM\NJSql;

class NJPipeline {
  protected $_in_pl = array();
  protected $_out_pl = array();

  public function do_in($data) {
    return $this->doit($data, true);
  }

  public function do_out($data) {
    return $this->doit($data, false);
  }

  protected function doit($data, $in) {
    if($in) {
      $_pls =& $this->_in_pl;
    }
    else {
      $_pls =& $this->_out_pl;
    }

    array_map(function($pipes, $col) use(&$data) {
      foreach($pipes as $pipe) {
        $func = array_shift($pipe);
        array_unshift($pipe, $col);
        $args = array_map(function($col) use($data) {
          return isset($data[$col]) ? $data[$col] : null;
        }, $pipe);
        $data[$col] = call_user_func_array($func, $args);
      }
    }, array_values($_pls), array_keys($_pls));

    return $data;
  }

  public function set($col, $in, $out) {
    foreach(array('in','out') as $type) {
      if($$type) {
        $pl = '_'.$type.'_pl';
        $refPL =& $this->$pl;
        if(empty($refPL[$col])) {
          $refPL[$col] = array();
        }
        is_array($$type) || $$type = array($$type);

        if(is_array(reset($$type))) {
          $is_multi = true;
        }
        elseif(is_callable(reset($$type))){
          $is_multi = false;
          foreach(array_slice($$type, 1) as $v) {
            if(is_callable($v) || is_array($v)) {
              $is_multi = true;
              break;
            }
          }
        }

        // 
        if(!$is_multi)
          $refPL[$col][] = $$type;
        else {
          $$type = array_map(function($v){
            return is_array($v) ? $v : array($v);
          }, $$type);
          $refPL[$col] = array_merge($refPL[$col], $$type);
        }
      }
    }

    return $this;
  }
}