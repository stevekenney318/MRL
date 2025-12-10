<?php
class Database
{
     
    private $host = "localhost";
    private $db_name = "u809830586_MRL_DB";
    private $username = "u809830586_MRL_DB";
    private $password = "7neGYdSZkFpR";
    public $dbconnect;
     
    public function dbConnection()
	{
     
	    $this->conn = null;    
        try
		{
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);			
        }
		catch(PDOException $exception)
		{
            echo "Connection error: " . $exception->getMessage();
        }
         
        return $this->conn;
    }
}
?>