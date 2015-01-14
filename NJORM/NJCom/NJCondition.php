<?php
/**
 * @Author: byamin
 * @Date:   2014-12-21 16:51:57
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-01-15 01:05:48
 */
namespace NJORM\NJCom;
use \NJORM\NJCom\NJStringifiable;
use \NJORM\NJMisc;
class NJCondition implements NJStringifiable{

  protected $_conditions;
  protected $_parameters;

  public function __construct() {
  }

  public static function fact($arg) {
    $class = get_called_class();
    $rc = new \ReflectionClass($class);

    if(!($arg instanceof $class)){
      $arg = $rc->newInstanceArgs();
      $arg->parse(func_get_args());
    }
    $inst = $rc->newInstanceArgs();
    $inst->addCondition($arg);

    return $inst;
  }

  protected function call_or() {
    if(func_num_args() < 1) {
      trigger_error('NJCondition "or" methods expects least 1 parameter.');
    }
    $class = get_class($this);
    if($arg instanceof $class)
      $this->addCondition('or', $arg);
    else
      $this->addCondition('or', self::fact(func_get_args()));
  }

  protected function call_and($arg) {
    if(func_num_args() < 1) {
      trigger_error('NJCondition "or" methods expects least 1 parameter.');
    }
    $class = get_class($this);
    if($arg instanceof $class)
      $this->addCondition('and', $arg);
    else
      $this->addCondition('and', self::fact(func_get_args()));
  }

  protected function call_close() {
    return self::fact($this);
  }

  public function __call($name, $args) {
    if(in_array($name, array('or', 'and', 'close'))) {
      call_user_func_array(array($this, 'call_'.$name), $args);
      return $this;
    }
    trigger_error('NJCondition does not undefined method: ' . $name);
  }

  protected function addCondition() {
    if(func_num_args() == 1) {
      $this->_conditions = array(func_get_arg(0));
    }
    elseif(func_num_args() == 2) {
      array_push($this->_conditions, func_get_arg(0), func_get_arg(1));
    }
    else {
      trigger_error('addCondition expects 1 or 2 parameters.');
    }
  }

  protected function parse($arg) {
    if(strpos($arg[0], '%') !== false) {
      $arg[0] = str_replace('%s', "'%s'", $arg[0]);
      $this->_conditions = call_user_func_array('sprintf', $arg);
      return $this;
    }

    do {
      /*
      if(count($arg) <= 1) {
        $stmt = preg_replace("", replacement, subject)
        $arg = preg_split('/.+['.NJMisc::supportedOperators('|').'].+/i', $stmt);
      }*/

      if(count($arg) <= 1) {
        $this->_conditions = array_shift($arg);
        break;
      }
      if(count($arg) <= 2) {
        $v = array_pop($arg);
        $arg[] = '=';
        $arg[] = $v;
      }
      if(count($arg) >= 3) {
        $arg[1] = NJMisc::formatOperator($arg[1]);

        // between
        if(in_array($arg[1], array('BETWEEN', 'NOT BETWEEN'))) {
          if(count($arg) < 4) {
            trigger_error('"between" operetaor expr expects 4 arguments');
          }

          $arg[2] = sprintf("%s AND %s", NJMisc::formatValue($arg[2]), NJMisc::formatValue($arg[3]));
        }

        // IS (NOT) NULL
        elseif(is_null($arg[2]) || is_bool($arg[2])) {
          $arg[1] = NJMisc::operatorForNull($arg[1]);
          if(is_null($arg[2]))
            $arg[2] = 'NULL';
          else
            $arg[2] = $arg[2] ? 'TRUE' : 'FALSE';
        }

        // (NOT) IN (...)
        elseif(is_array($arg[2])) {
          if(empty($arg[2])) {
            $arg[1] = NJMisc::operatorForNull($arg[1]);
            $arg[2] = 'NULL';
          }
          else {
            $arg[1] = NJMisc::operatorForArray($arg[1]);
            $arg[2] = NJMisc::formatValue($arg[2]);
          }
        }

        // A VALUE OR A Field
        else {
          $arg[2] = NJMisc::formatValue($arg[2]);
        }

        $arg[0] = NJMisc::formatFieldName($arg[0]);
        $this->_conditions = sprintf("%s %s %s", $arg[0], $arg[1], $arg[2]);
      }
    }
    while(0);

    return $this;
  }

  public function stringify() {
    if(is_array($this->_conditions)) {
      $class = get_class($this);
      $strs = array();
      foreach($this->_conditions as $cond) {
        if(is_string($cond)) {
          $strs[] = strtoupper($cond);
        }
        elseif($cond instanceof $class) {
          $strs[] = $cond->stringify();
        }
        else {
          trigger_error('unexpected type for condition.' . gettype($cond));
        }
      }
      $str_c = implode(' ', $strs);
      if(count($strs) > 1)
        $str_c = sprintf('(%s)', $str_c);
      return $str_c;
    }
    if(is_string($this->_conditions) or is_numeric($this->_conditions)) {
      return $this->_conditions;
    }
    trigger_error('unexpected type for condtion:' . gettype($this->_conditions));
  }

  public function __toString() {
    return "WHERE " . $this->stringify();
  }
}