<?php
/**
 * @Author: byamin
 * @Date:   2015-01-01 12:21:16
 * @Last Modified by:   byamin
 * @Last Modified time: 2015-02-11 00:47:30
 */


use \NJORM\NJTable;
use \NJORM\NJQuery;

class NJQueryTest extends PHPUnit_Framework_TestCase {

  public function setUp(){
    NJTable::define('rct_ec_cards', 'cards')
      ->primary('ID', 'id')
      ->field('card_author', 'author')
      ->field('card_name', 'name')
      ->field('card_theme', 'theme');
  }

  function testNJQuery() {
    $query = new NJQuery('cards');
    $query
    ->select('author', 'name', 'theme')
    ->limit(5,100)
    ->where('author', '>', 1)
    ->where('theme', 'hello')
    ->sortAsc('theme');

    $this->assertEquals('SELECT `card_author` `author`,`card_name` `name`,`card_theme` `theme` FROM `rct_ec_cards` WHERE `card_author`>1 AND `card_theme`=\'hello\' ORDER BY `theme` LIMIT 5,100', $query->sqlSelect());

    $query->fetch();
  }
}