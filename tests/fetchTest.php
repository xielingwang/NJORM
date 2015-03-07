<?php
/**
 * @Author: AminBy
 * @Date:   2015-03-07 18:26:39
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-07 18:38:21
 */

use \NJORM\NJORM;

class NJQueryDeleteTest extends PHPUnit_Framework_TestCase {

  public function testFetchPair() {
    $stmt = NJORM::inst()->query("select `user_id` `id`,`user_balance` `balance`,`user_name` `name` FROM `qn_users` LIMIT 5");
    // print_r($stmt->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_COLUMN|\PDO::FETCH_ASSOC));die;
    $stmt = NJORM::inst()->query("select `user_id` `name`,`user_balance` `value` FROM `qn_users` LIMIT 5");
    print_r($stmt->fetchAll(\PDO::FETCH_NUM|\PDO::FETCH_KEY_PAIR));die;
  }
}