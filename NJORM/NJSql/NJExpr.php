<?php
/**
 * @Author: byamin
 * @Date:   2015-02-17 19:56:26
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-02-23 21:33:35
 */
namespace NJORM\NJSql;
class NJExpr extends NJObject{
  function __construct() {
    if(func_num_args() > 0) {
      $this->parse(func_get_args());
    }
  }

  public function parse($args) {
    // 1.transfer % to @#PCNT#@ and ? to @#QUSTN#@
    $format = preg_replace_callback("/'[^']*[%?][^']*'/", function($matches){
      return str_replace(array('%','?'), array('@#PCNT#@','@#QUSTN#@'), $matches[0]);
    }, array_shift($args));

    // 2.capture printf arguments and their offset
    $format = str_replace('%s', "'%s'", $format);
    $r = preg_match_all("/%[sdfl]/", $format, $matches, PREG_PATTERN_ORDER|PREG_OFFSET_CAPTURE);
    $offsetPtf = array();
    foreach($matches[0] as $_){
      $offsetPtf[] = $_[1];
    }

    // 3.catpure question marks and their offset
    $offsetQM = array();
    $r = 0;
    while(($r = strpos($format, '?', $r)) !== false) {
      $offsetQM[] = $r++;
    }

    // 4. process the sprintf arguments and bindPara parameters
    $offsetMarks = array_merge($offsetPtf, $offsetQM);
    if(count($args) < count($offsetMarks)) {
      trigger_error('Too few arguments for NJCondition::parse()');
    }
    sort($offsetMarks);
    $offsetMarks = array_flip($offsetMarks);
    $args4sprintf = array();
    foreach($offsetPtf as $idx) {
      $args4sprintf[] = $args[$offsetMarks[$idx]];
    }

    // 5.get parameters and condition statement
    $this->_SetParameters(array_diff($args, $args4sprintf));
    array_unshift($args4sprintf, $format);
    $this->_SetValue(str_replace(array('@#PCNT#@','@#QUSTN#@'), array('%','?'), call_user_func_array('sprintf', $args4sprintf)));

    return $this;
  }
}