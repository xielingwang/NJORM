<?php
/**
 * @Author: Amin by
 * @Date:   2014-12-15 10:22:32
 * @Last Modified by:   AminBy
 * @Last Modified time: 2015-03-05 15:30:07
 */
namespace NJORM;

class NJDb {

  public static function execute($sql, $params) {

    // type: prepare/execute
    if($params) {
      $stmt = NJORM::inst()->prepare($sql);

      if(!$stmt->execute($params)) {
        echo $stmt->queryString.PHP_EOL;
        echo $stmt->errorCode().PHP_EOL;
        print_r($stmt->errorInfo());
        throw new \Exception("bindParam Error");
      }
    }

    // type: query
    else {
      $stmt = NJORM::inst()->query($sql);
    }

    return $stmt;
  }

  /*
   CREATE TABLE `qn_users` (
     `user_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
     `user_name` varchar(128) NOT NULL,
     `user_pass` varchar(128) NOT NULL,
     `user_balance` float DEFAULT '0',
     `user_email` varchar(128) NOT NULL DEFAULT '',
     `user_created` int(11) unsigned DEFAULT NULL,
     `user_updated` int(11) unsigned DEFAULT NULL,
     PRIMARY KEY (`user_id`)
   ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
   CREATE TABLE `qn_posts` (
    `post_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `post_user_id` bigint(20) unsigned NOT NULL,
    `post_title` varchar(128) NOT NULL,
    `post_content` varchar(1024) NOT NULL,
    `post_created` int(11) unsigned DEFAULT 0,
    PRIMARY KEY (`post_id`)
   ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
   CREATE TABLE `qn_tags` (
    `tag_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `tag_name` varchar(128) NOT NULL,
    `tag_created` int(11) unsigned DEFAULT 0,
    PRIMARY KEY (`tag_id`)
   ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
   CREATE TABLE `qn_post_tag` (
    `tag_id` bigint(20) NOT NULL,
    `post_id` bigint(20) NOT NULL,
    PRIMARY KEY (`tag_id`, `post_id`)
   ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
   */
  protected function get_dsn($driver, $config) {
    $_config = array();
    $config = array_change_key_case($config);

    foreach($this->dsn_keys($driver) as $k) {
      if(array_key_exists($k, $config)) {
        $_config[] = $k.'='.$config[$k];
      }
    }
    return $driver.':'.implode(';', $_config);
  }

  protected function dsn_keys($driver) {
    $dsns = [
    'mysql' => ['host','port','dbname','unix_socket','charset'],
    ];
    return $dsns[$driver];
  }

  protected $_configs = array();
  protected $_using = null;
  public function config() {
    if(func_num_args() < 2) {
      return $this->_configs[$this->_using];
    }

    list($name, $value) = func_get_args();
    $name = strtolower(trim($name));

    if(!$this->_using)
      $this->_using = $name;

    $this->_configs[$name] = $value;

    return $this;
  }
  public function choose($name) {
    $name = strtolower(trim($name));
    if(!array_key_exists($name, $this->_configs)) {
      trigger_error(sprintf('DB Config "%s" is undefined'));
    }
    $this->_using = $name;
    return $this;
  }

  public function mysql($config) {
    $dsn = $this->get_dsn('mysql', $config);
    $options = [];
    if(empty($config['charset'])) {
      $options[\PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . $config['charset'];
    }
    $user = $config['user'];
    $pass = $config['pass'];

    return compact('dsn', 'user', 'pass', 'options');
  }

  public static function getInstance() {
    static $inst;
    if(!$inst) {
      $inst = new static();
      $inst
      ->config('macAir', $inst->mysql([
        'dbname' => 'test',
        'unix_socket' => '/private/tmp/mysql.sock',
        'user' => 'root',
        'pass' => 'root',
        'charset' => 'utf8',
        ]))
      ->config('macMini', $inst->mysql([
        'dbname' => 'qndb',
        'host' => 'localhost',
        'user' => 'root',
        'pass' => 'password',
        'charset' => 'utf8',
        ]))
      ->choose('macMini');
    }
    return $inst;
  }

  function __set($name, $value) {
    $this->config($name, $value);
  }
}