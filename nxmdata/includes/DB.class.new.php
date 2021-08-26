<?php 

require_once("db.config.php");

class Database 
{
    private $_db;
    static $_instance;
	
	private $host      = DB_HOST;
    private $user      = DB_USER;
    private $pass      = DB_PASS;
    private $dbname    = DB_NAME;

    private function __construct() {
        $this->_db = new PDO('mysql:host='.$this->host.';dbname='.$this->dbname, $this->user, $this->pass);
        $this->_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    private function __clone(){}

    public static function getInstance() {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }
		
        return self::$_instance;
    }
	
	public function getDB(){
		return $this->_db;
	}
	
	
}

?>