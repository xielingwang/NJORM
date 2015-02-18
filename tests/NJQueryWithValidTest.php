<?php
/**
 * @Author: AminBy
 * @Date:   2015-02-17 22:21:26
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-02-19 03:44:42
 */

use \NJORM\NJSql\NJTable;
use \NJORM\NJQuery;

class NJQueryWithValidTest extends PHPUnit_Framework_TestCase {

  public function setUp(){
    if(!NJTable::defined('qn_users')) {

      NJTable::define('qn_users', 'users')
        ->primary('user_id', 'uid')

        ->field('user_name', 'name')
        ->valid('用户名称{1-0}-{1-1}个字符', 'notEmpty', ['lengthBetween', 7,30])
        ->valid('用户名称要唯一', 'unique')

        ->field('user_pass', 'pass')

        ->field('user_balance', 'balance')
        ->valid('余额必须是个小数', 'float')

        ->field('user_email', 'email');
    }
  }

  public function testQN(){
    $query = new NJQuery('users');
    $query->sqlInsert(array(
      'name' => 'insert-name',
      'balance' => '0.22',
      ));
  }
}