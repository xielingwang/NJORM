<?php
/**
 * @Author: AminBy
 * @Date:   2015-02-23 20:10:16
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-04-06 22:06:17
 */
use \NJORM\NJORM;
use \NJORM\NJException;
use \NJORM\NJSql\NJTable;
use \NJORM\NJSql\NJExpr;
use \NJORM\NJSql\NJPipeline;

class NJExprTest extends PHPUnit_Framework_TestCase {

  function testNJPipeline() {
    $pipeline = new NJPipeline();
    $pipeline->set('arr', 'serialize', 'unserialize');
    $pipeline->set('pswd', [function($v){return 'abc'.$v.'def';}, function($v){ return md5($v);}], null);
    $pipeline->set('unarr', 'json_decode', 'json_encode');

    $data = [
      'arr' => [1,3,4,5, 'c'=>'d'],
      'unarr' => '{"d":"f","a":"c"}',
      'pswd' => 'abc12345',
      ];
    $inr = $pipeline->do_in($data);

    $this->assertEquals(md5('abc'.$data['pswd'].'def'), $inr['pswd']);
    $this->assertEquals((object)['a'=>'c','d'=>'f'], $inr['unarr']);
    $this->assertEquals('a:5:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:5;s:1:"c";s:1:"d";}', $inr['arr']);

    $outr = $pipeline->do_out($inr);

    $this->assertEquals(md5('abc'.$data['pswd'].'def'), $outr['pswd']);
    $this->assertEquals($data['unarr'], $outr['unarr']);
    $this->assertEquals($data['arr'], $outr['arr']);
  }

  public function testNJPipeline2() {

    NJTable::define('qn_users', 'users')
      ->primary('user_id', 'id')

      ->field('user_name', 'name')
      ->valid('unique', ['lengthBetween', 7, 16])
      ->pl_pop(function($name, $id){
        return implode('_', func_get_args());
      }, 'id')

      ->field('user_pass', 'pass')
      ->valid(['lengthBetween', 7, 16])
      ->pl_push(function($v) { return md5($v);})

      ->field('user_balance', 'bal')
      ->valid('float', 'positive')

      ->field('user_email', 'eml')
      ->valid('email','unique')

      ->field('attrs', 'att')
      ->pl_push('serialize')
      ->pl_pop('unserialize', function($arr) {
        return array_combine(array_keys($arr), array_map(function($v) {
          return ucfirst($v);
        }, array_values($arr)));
      })

      ->field('user_created', 'ct')->default(new NJExpr('unix_timestamp()'))
      ->field('user_updated', 'ut')->defaultUpd(new NJExpr('unix_timestamp()'));

    $data = array(
      'name' => 'hello'.rand(0,999999),
      'pass' => 12345678,
      'bal' => 20,
      'eml' => 'hello'.rand(0,999).'@'.rand(10,99).'.com',
      'att' => [
      'hello' => 'world',
      'ya' => 'miedie',
      ]
      );
    NJORM::error(function($str){
      echo $str;
    });
    try {
      $r = NJORM::inst()->users->insert($data);
      $r2 = NJORM::inst()->users[$r['id']];

      $this->assertEquals(implode('_', [$data['name'], $r['id']]), $r2['name']);
      $this->assertEquals([
      'hello' => 'World',
      'ya' => 'Miedie',
      ], $r2['att']);
    }
    catch(NJException $e) {
      var_dump($e->getMsgs());
      $this->assertTrue(false, 'a NJException');
    }
  } 
}