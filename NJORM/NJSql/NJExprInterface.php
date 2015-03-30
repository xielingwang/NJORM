<?php
/**
 * @Author: AminBy
 * @Date:   2015-03-30 17:57:13
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-30 18:51:36
 */
namespace NJORM\NJSql;

interface NJExprInterface {
  function stringify();
  function parameters();
  function isEnclosed();
}