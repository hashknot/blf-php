<?php
class mysqldb
{
	public $server = "";
	public $username = "";
	public $password = "";
	public $dbname = "";
	public $link;
	public $fields='*';
	public $table='testtable';
	public $where='1';
	public $order='';
	public $limit='';
    public $values ='';
    public $columns ='';

	public function connect()
	{
		$this->link = mysql_connect($this->server,$this->username,$this->password) or die($this->error());
		$this->selectdb(); 
	}

	public function selectdb()
	{
		mysql_select_db($this->dbname) or die($this->error());
	}

	public function close()
	{
		return mysql_close($this->link) or die($this->error());
	}

	public function error()
	{
		return mysql_error($this->link);
	}

	public function select()
	{
		$qry = "SELECT ".$this->fields." FROM ".$this->table." WHERE ".$this->where." ".$this->order." ".$this->limit;
		$result = mysql_query($qry)  or die (mysql_error());
		return $result;
	}

	public function update()
	{
		$qry = "UPDATE ".$this->table." SET ".$this->fields." WHERE ".$this->where;
		mysql_query($qry) or die (mysql_error());
	}
	
    public function insert()
	{
		$qry = "INSERT INTO ".$this->table." (".$this->columns.") VALUES (".$this->values.")";		
		mysql_query($qry) or die (mysql_error());
	}
}
?>