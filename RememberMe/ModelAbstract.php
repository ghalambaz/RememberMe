<?php
/* Toloe Fanavaran AWAN
 * YanaGroup Framework (YFrame)
 * Programmer: ali ghalambaz <aghalambaz@gmail.com>
 * Version: 0.01
 * Date: 12/24/11 
 * Time: 4:58 PM */


namespace RememberMe;


abstract class ModelAbstract
{
    private static $active = array();
    protected  $connection = null;
    public  function __construct($auth,$connect_it = true,$name = 'noname' ,$user = null, $engine = null)
    {
        if($connect_it)
            $this->connection = self::connect($auth,$name = 'noname' ,$user, $engine);
    }
    protected  static function connect($auth,$name = 'noname' ,$user = null, $engine = null)
    {

        $dsn = self::mysql($auth['name'],$auth['host']);
        try {
            $options = array(
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                \PDO::ATTR_CASE,\PDO::CASE_NATURAL,
                \PDO::ATTR_DEFAULT_FETCH_MODE=>\PDO::FETCH_ASSOC,
                \PDO::ATTR_ERRMODE =>\PDO::ERRMODE_EXCEPTION
            );
            $con = new \PDO($dsn, $auth['user'],$auth['pass'],$options);
            self::$active['connection'] = $con;
            self::$active['name'] = $name;
            self::$active['user'] = $user;
            self::$active['engine'] = $engine;
            return $con;
        } catch (\PDOException $e) {
            echo ('Remember Me : No Link to Database - Try again later - '.$e->getMessage());
        }
        return null;
    }
    private static function mysql($db_name, $host = 'local', $port = 3306)
    {
        return "mysql:host=$host;port=$port;dbname=$db_name;charset=UTF8";
    }
    protected  static function getActiveConnections()
    {
        return self::$active;
    }
}
