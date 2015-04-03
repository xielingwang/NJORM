<?php
/**
 * @File: NJPipeline.php
 * @Author: AminBy (xielingwang@gmail.com)
 * @Date:   2015-04-03 23:39:53
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-04-04 00:27:09
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
      if(!is_array($pipes)) {
        var_dump($pipes); die;
      }
      foreach($pipes as $pipe) {
        $data[$col] = isset($data[$col])?$pipe($data[$col]):$pipe(null);
      }
    }, array_values($_pls), array_keys($_pls));

    return $data;
  }

  public function set($col, $in, $out) {
    if($in) {
      if(empty($this->_in_pl[$col])) {
        $this->_in_pl[$col] = array();
      }
      is_array($in) || $in = array($in);
      $this->_in_pl[$col] = array_merge($this->_in_pl[$col], $in);
    }

    if($out) {
      if(empty($this->_out_pl[$col])) {
        $this->_out_pl[$col] = array();
      }
      is_array($out) || $out = array($out);

      $this->_out_pl[$col] = array_merge($this->_out_pl[$col], array_reverse($out));
    }

    return $this;
  }
}