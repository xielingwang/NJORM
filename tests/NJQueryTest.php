<?php
/**
 * @name: byamin
 * @Date:   2015-01-01 12:21:16
 * @Last Modified by:   Amin by
 * @Last Modified time: 2015-02-13 18:35:08
 */


use \NJORM\NJSql\NJTable;
use \NJORM\NJQuery;

class NJQueryTest extends PHPUnit_Framework_TestCase {

  public function setUp(){
    if(!NJTable::defined('qn_users')) {
      NJTable::define('qn_users', 'users')
        ->primary('user_id', 'uid')
        ->field('user_name', 'name')
        ->field('user_pass', 'pass')
        ->field('user_email', 'email');
    }
  }

  function testNJQuery() {
    $query = new NJQuery('users');
    $query
    ->select('name', 'pass', 'email')
    ->limit(0,2)
    ->where('uid', '>', 1)
    ->where('email', '0222')
    ->sortAsc('email');

    $this->assertEquals('SELECT `user_name` `name`,`user_pass` `pass`,`user_email` `email` FROM `qn_users` WHERE `user_id` > 1 AND `user_email` = \'0222\' ORDER BY `email` LIMIT 2', $query->sqlSelect());

    $model = $query->fetch();
    $this->assertEquals('gogog', $model['name']);
  }
}