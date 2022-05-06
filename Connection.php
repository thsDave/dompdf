<?php

	require_once 'config.php';

	class Connection
	{
		private $socket;
	    private $host;
    	private $user;
    	private $pwd;
    	private $db;
    	private $port;
    	private $charset;
	    private $con;

	    public function __construct()
	    {
	    	$this->socket = DB_SOCKET;
	    	$this->host = DB_HOST;
	    	$this->user = DB_USER;
	    	$this->pwd = DB_PWD;
	    	$this->db = DB_NAME;
	    	$this->port = DB_PORT;
	    	$this->charset = DB_CHARSET;
	    }

	    public function connect()
	    {
	    	try
	    	{
	    		$mysql = ($this->socket == '') ? 'host='.$this->host : 'unix_socket='.$this->socket;

	    		$str_connect = "mysql:{$mysql};dbname={$this->db};port={$this->port};charset={$this->charset}";

	    		$options = [
	    			PDO::ATTR_CASE => PDO::CASE_LOWER,
	    			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	    			PDO::ATTR_ORACLE_NULLS => PDO::NULL_EMPTY_STRING,
	    			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
	    		];

	    		$this->con = new PDO($str_connect, $this->user, $this->pwd, $options);

    			return $this->con;
	    	}
	    	catch (PDOException $e)
	    	{
	    		load_view('db_err', utf8_encode($e->getMessage()));
	    	}
	    }
	}