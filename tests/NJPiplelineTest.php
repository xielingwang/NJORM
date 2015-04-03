<?php
/**
 * @Author: AminBy
 * @Date:   2015-02-23 20:10:16
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-04-04 00:28:55
 */
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
}