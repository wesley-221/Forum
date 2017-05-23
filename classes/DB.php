<?php
class DB
{
	// ================================================
	// Functions
	// DB::getInstance() -> query, get, delete,
	//						insert, update, results,
	//						first, error, count();


    private static $_instance = null;
    private $_pdo,
		    $_query,
		    $_error = false,
		    $_results,
		    $_counts = 0;

	private function __construct()
	{
		try
		{
			$this -> _pdo = new PDO('mysql:host=' . Config::get('mysql/host') . ';dbname=' . Config::get('mysql/db') . ';', Config::get('mysql/username'), Config::get('mysql/password'));
		}

		catch(PDOException $e)
		{
			die($e -> getMessage());
		}
	}

	// ==================================================================
	// Required to execute a query, DB::getInstance()->ENTERFUNCTIONHERE
	// Creates PDO instance depending on if there is an instance or not
	public static function getInstance()
	{
		if(!isset(self::$_instance))
		{
			self::$_instance = new DB();
		}
		return self::$_instance;
	}

	// ==========================================================================================================
	// <summary>
	//     executes a query
	// </summary>
	//
	// DB::getInstance()->query("SELECT something FROM sometable WHERE something = ? AND something = ?", array(
	//	'somevalue',
	//	'someothervalue'));
	public function query($sql, $params = array())
	{
		$this -> _error = false;

		if($this -> _query = $this -> _pdo -> prepare($sql))
		{
			$x = 1;
			if(count($params))
			{
				foreach($params as $param)
				{
					$this -> _query -> bindValue($x, $param);
					$x ++;
				}
			}

			if($this->_query->execute())
			{
				$this -> _results = $this -> _query -> fetchAll(PDO::FETCH_OBJ);
				$this -> _count = $this -> _query -> rowCount();
			}
			else
			{
				$this -> _error = true;
			}
		}

		return $this;
	}

	private function action($action, $table, $where = array())
	{
		if(count($where) === 3)
		{
			$operators = array('=', '>', '<', '>=', '<=');

			$field		= $where[0];
			$operator 	= $where[1];
			$value 		= $where[2];

			if(in_array($operator, $operators))
			{
				$sql = "{$action} FROM {$table} WHERE {$field} {$operator} ?";

				if(!$this -> query($sql, array($value)) -> error())
				{
					return $this;
				}
			}
		}

		return false;
	}

	// ============================================================
	// <summary>
	//	   gets ALL data from the given query
	// </summary>
	//
	// DB::getInstance()->get('users', array('id', '=', '1'));
	public function get($table, $where)
	{
		return $this -> action("SELECT *", $table, $where);
	}

	// ===============================================================
	// <summary>
	//	   deletes data from a table
	// </summary>
	//
	// DB::getInstance()->delete('users', array('id', '=', '7'));
	public function delete($table, $where)
	{
		return $this -> action("DELETE", $table, $where);
	}

	// ===========================================
	// <summary>
	//	   inserts data in a table
	// </summary>
	//
	// DB::getInstance()->insert('users', array(
	// 		'username' 	=> 'testuser',
	//		'password' 	=> 'testpassword',
	//		'salt'		=> 'testsalt'
	//		));
	public function insert($table, $fields = array())
	{
		if(count($fields))
		{
			$keys = array_keys($fields);
			$values = '';
			$x = 1;

			foreach($fields as $field)
			{
				$values .= '?';

				if($x < count($fields))
				{
					$values .= ', ';
				}

				$x++;
			}

			$sql = "INSERT INTO {$table} (`" . implode('`, `', $keys) . "`) VALUES({$values})";

			if(!$this -> query($sql, $fields)->error())
			{
				return true;
			}
		}

		return false;
	}

	// <summary>
	//	   updates data in a table on the given
	// </summary>
	//
	// DB::getInstance()->update('users', array(
	// 			'username' 	=> 'testuser',
	//			'password' 	=> 'testpassword',
	//			'salt'		=> 'testsalt'
	//		), array(
	//			'id', '=', '1'
	//		));

	public function update($table, $fields = array(), $where = array())
	{
		if(count($where) === 3)
		{
			$operators = array('=', '>', '<', '>=', '<=');

			$field 		= $where[0];
			$operator 	= $where[1];
			$value1		= $where[2];

			$set = '';
			$x = 1;

			if(in_array($operator, $operators))
			{
				foreach($fields as $name => $value)
				{
					$set .= "{$name} = ?";

					if($x < count($fields))
					{
						$set .= ', ';
					}

					$x ++;
				}

				$fields['value'] = $value1;
				$sql = "UPDATE {$table} SET {$set} WHERE {$field} {$operator} ?";

				if(!$this->query($sql, $fields)->error())
				{
					return true;
				}
			}
		}

		return false;
	}

	// ==================================================================================================
	// <summary>
	//	   returns all the results from the query
	// 	   this function can return MULTIPLE results, if you want only one it's easier to use "first()"
	// </summary>
	//
	// $query = DB::getInstance()->query('SELECT username FROM users WHERE userid = ?', array('1'));
	/*
		if($query)
		{
			foreach($query->results() as $user)
			{
				echo $user->username;
			}

			echo $query->result()[#]->username;
		}
	*/
	public function results()
	{
		return $this -> _results;
	}

	// ==================================================================================================
	// <summary>
	//	   returns the first result of a query
	// 	   use this when you want to get one result
	// </summary>
	//
	// $query = DB::getInstance()->query('SELECT username FROM users WHERE userid = ?', array('1'));
	// echo $query->first()->username;
	public function first()
	{
		return $this -> results()[0];
	}

	// ==================================================================================================
	// <summary>
	//	   returns whether the qeury gave an error back or not (true/false)
	// </summary>
	//
	// $query = DB::getInstance()->query('SELECT username FROM users WHERE userid = ?', array('1'));
	/*
		if($query->error())
		{
			echo 'Couldn't execute query';
		}
		else
		{
			echo 'Query has been executed';
		}
	*/
	public function error()
	{
		return $this -> _error;
	}

	// =====================================================================================================================
	// <summary>
	// 	   returns the amount of results given by the query (integer: 1, 2, 3, 4, etc)
	// </summary>
	//
	// $query = DB::getInstance()->query('SELECT username FROM users WHERE userid = ? OR userid = ?', array('1', '2'));
	// echo $query->count();
	public function count()
	{
		return $this -> _count;
	}
}
