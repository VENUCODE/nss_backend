<?php
class db{
    private $dbhost="localhost";
    private $dbuser="root";
    private $dbpassword="";
    private $dbname="nss";
    public function connect(){
        $mysql_connect_str = 'mysql:host='.$this->dbhost.';dbname='.$this->dbname;
        $dbConnection=new PDO($mysql_connect_str,$this->dbuser,$this->dbpassword);
        $dbConnection->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        return $dbConnection;
    }
}