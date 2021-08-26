<?php

require_once($_SERVER["HOME"]."/public_html/includes/db.config.php");

class Database{
    private $host      = DB_HOST;
    private $user      = DB_USER;
    private $pass      = DB_PASS;
    private $dbname    = DB_NAME;
 
    private $dbh;
    private $error;
    private $stmt;
    
 
    public function __construct($test=false){
        if($test){
			$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_SANDBOX_NAME;
		}        
        else{
			$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;
		}		
        $options = array(
            PDO::ATTR_PERSISTENT    => true,
            PDO::ATTR_ERRMODE       => PDO::ERRMODE_EXCEPTION
        );
        
        try{
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        }        
        catch(PDOException $e){
            $this->error = $e->getMessage();
        }
    }
    
    public function query($query){
		$this->stmt = $this->dbh->prepare($query);
	}
	
	public function bind($param, $value, $type = null){
		if (is_null($type)) {
			switch (true) {
				case is_int($value):
					$type = PDO::PARAM_INT;
					break;
				case is_bool($value):
					$type = PDO::PARAM_BOOL;
					break;
				case is_null($value):
					$type = PDO::PARAM_NULL;
					break;
				default:
					$type = PDO::PARAM_STR;
			}
		}
		$this->stmt->bindValue($param, $value, $type);
	}
	
	public function execute(){
		return $this->stmt->execute();
	}
	
	public function resultSet(){
		$this->execute();
		return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	
	public function fieldValue(){
		$this->execute();
		return $this->stmt->fetch(PDO::FETCH_ASSOC);
	}
	
	public function rowCount(){
		return $this->stmt->rowCount();
	}
	
	public function lastInsertId(){
		return $this->dbh->lastInsertId();
	}
	public function beginTransaction(){
		return $this->dbh->beginTransaction();
	}
	
	public function endTransaction(){
		return $this->dbh->commit();
	}
	
	public function cancelTransaction(){
		return $this->dbh->rollBack();
	}
	
	public function debugDumpParams(){
		return $this->stmt->debugDumpParams();
	}
}

?>
