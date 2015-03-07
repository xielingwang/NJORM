<?php
/**
 * @Author: AminBy
 * @Date:   2015-03-05 15:52:11
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-07 17:25:31
 */

use \NJORM\NJSql\NJTable;
use \NJORM\NJSql\NJExpr;
use \NJORM\NJORM;
use \NJORM\NJModel;
use \NJORM\NJCollection;

class NJQueryDeleteTest extends PHPUnit_Framework_TestCase {

  public function setUp(){
    if(!NJTable::defined('qn_users')) {
      NJTable::define('qn_users', 'users')
        ->primary('user_id', 'uid')
        ->field('user_name', 'name')
        ->field('user_pass', 'pass')
        ->field('user_balance', 'balance')
        ->field('user_email', 'email')
        ->field('user_created', 'ct')
        ->field('user_updated', 'ut')
        ;
    }
  }

  function testQueryDelete() {
    $query = NJORM::inst()->users;
    $query->where('uid > 10')
    ->limit(3,1);

    $query->delete();

    extract(NJORM::lastquery(), EXTR_PREFIX_ALL, 'exec');
    $this->assertEquals("DELETE FROM `qn_users` WHERE `user_id` > 10 LIMIT 3", $exec_sql);
  }
}