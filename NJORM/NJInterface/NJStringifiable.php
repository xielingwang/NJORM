<?php
/**
 * @Author: byamin
 * @Date:   2015-01-04 23:35:13
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-02-16 00:31:13
 */
namespace NJORM\NJInterface;
interface NJStringifiable {
  function stringify();
  function __toString();
}