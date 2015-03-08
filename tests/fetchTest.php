<?php
/**
 * @Author: AminBy
 * @Date:   2015-03-07 18:26:39
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-09 00:24:44
 */

use \NJORM\NJORM;

class NJQueryDeleteTest extends PHPUnit_Framework_TestCase {

  public function testFetchPair() {
    $stmt = NJORM::inst()->query("select `user_created` `name`,`user_id` `value` FROM `qn_users` LIMIT 5");
    // print_r($stmt->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_COLUMN|\PDO::FETCH_ASSOC));die;
    // $stmt = NJORM::inst()->query("select * FROM `qn_users` LIMIT 5");
    print_r($stmt->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_GROUP));die;
    print_r($stmt->fetchAll(\PDO::FETCH_KEY_PAIR));die;
    print_r($stmt->fetchAll(PDO::FETCH_COLUMN, 1));die;
  }
}