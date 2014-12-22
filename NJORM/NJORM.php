<?php
/**
 * @Author: Amin by
 * @Date:   2014-12-15 10:22:32
 * @Last Modified by:   Amin by
 * @Last Modified time: 2014-12-19 11:45:37
 */

class NJORM {
  function __get($table) {
    return (new NJQuery())->from($table);
  }
}