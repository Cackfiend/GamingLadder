<?php
    /*
	*
         * DB: Driver, which works with MySQL database
	* @author Khramkov Ivan.
	* 
	*/
    class DB {
	    /*
		* Database link identifier
		*@var integer
		*/
	    private $db_handle;
		/*
		* Object model, wich works with database queries.
		*@var object
		*/
		var $query;
		/*
		* Constructor
		*@param object $config
		*/
	    function __construct($config) {
			$this->db_handle = mysql_connect($config->mysql_server, $config->mysql_user, $config->mysql_password);
			mysql_select_db($config->mysql_database_name, $this->db_handle);
			mysql_query('SET NAMES utf8', $this->db_handle);
		}
		/*
		* Destructor
		*/
		function __destruct() {
		    // Kill an query object...
			unset($this->query);
		}
		/*
		*@function execute
		*@param string|null $query
		*@return object
		*/
		public function execute($query = NULL) {
		    //echo "$query<br />"; /* Debug print */
			//if $query is set, then we execute query localy, using mysql_query function....
			//If $query is not set, then we call execute method of current query object...
		    $result = (isset($query))? mysql_query($query, $this->db_handle) : $this->query->execute($this->db_handle);
			$mysql_error = mysql_error($this->db_handle);
			if ($mysql_error) {
			    throw new Exception($mysql_error);
			}
			mysql_close($this->db_handle);
			return $result;
		}
		/*
		*@function get_all
		*@param string|null $field
		*@param string|null $query
		*@return array
		*/
		public function get_all($field = NULL, $query = NULL) {
		    $tmp = (isset($query))? $this->execute($query) : $this->query->execute($this->db_handle);
		    $arr = array();
			while($row = mysql_fetch_assoc($tmp)) {
			    array_push($arr, ((isset($field))? $row[$field] : $row));
			}
			return $arr;
		}
		/*
		*@function get_row
		*@param string|null $query
		*@return array
		*/
		public function get_row($query = NULL) {
		    return mysql_fetch_assoc(((isset($query))? $this->execute($query) : $this->query->execute($this->db_handle)));
		}
		/*
		*@function get_row_count
		*@param string|null $query
		*@return integer
		*/
		public function get_row_count($query = NULL) {
		    return mysql_num_rows(((isset($query))? $this->execute($query) : $this->query->execute($this->db_handle)));
		}
		/*
		*@function get_value
		*@param string $field
		*@param string|null $query
		*@return string|integer|float|null
		*/
		public function get_value($field, $query = NULL) {
		    $tmp = $this->get_row($query);
			return $tmp[$field];
		}
		/*
		*@function insert
		*@param array $params
		*@param string $table
		*@return integer
		*/
		public function insert($params, $table) {
		    $query = new DB_Query_INSERT($this->db_handle);
			return $query->insert($params, $table);
		}
		/*
		*@function update
		*@param array $params
		*@param string $table
		*@param object|null $condition
		*/
		public function update($params, $table, $condition = NULL) {
		    $query = new DB_Query_UPDATE($this->db_handle);
			$query->update($params, $table, $condition);
		}
		/*
		*@function delete
		*@param string $table
		*@param object|null $condition
		*/
		public function delete($table, $condition = NULL) {
		    $query = new DB_Query_DELETE($this->db_handle);
			$query->delete($table, $condition);
		}
		/*
		*@function create_table
		*@param string $table_name
		*@param array|null $params
		*@return object
		*/
		public function create_table($table_name, $params = NULL) {
		    $query = "CREATE TABLE IF NOT EXISTS `$table_name`";
			$str = "`id` int(11) NOT NULL auto_increment";
			foreach ($params as $key => $value) {
			    $str .= ", `".$value['property']."` ".$value['settings'];
			}
			$query .= " ($str, PRIMARY KEY (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
		    return $this->execute($query);
		}
	}
	
    /*
	*
    * DB_Query: MySQL Query object
	* 
	*/
    class DB_Query {
	    /*
		* MySQL condition object
		*@var object
		*/
	    protected $condition;
		/*
		* Parameters
		*@var array
		*/
		protected $params;
		/*
		* Table name
		*@var string
		*/
		protected $table;
		/*
		* MySQL identifier
		*@var integer
		*/
		protected $db_handle;
		/*
		* Constructor
		*@param integer|null $db_handle
		*/
	    function __construct($db_handle = NULL) {
		    $this->db_handle = $db_handle;
		}
		/*
		* Destructor
		*/
		function __destruct() {
		    //Kill condition object
		    unset($this->condition);
		}
		/*
		*@function to_string
		*/
		public function to_string() {
		    //Nothing to do, because it's abstract.
		}
		/*
		*@function execute
		*@param integer $mysql_handle
		*@return object
		*/
		public function execute($mysql_handle) {
		    //echo $this->to_string()."<br />";
		    $result = mysql_query($this->to_string(), $mysql_handle);
			$mysql_error = mysql_error($mysql_handle);
			if ($mysql_error) {
			     throw new Exception($mysql_error);
			}
			else {
			    return $result;
			}
		}
		/*
		*@function setup
		*@param array $params
		*@param string $table
		*@param object|null $condition
		*@return object
		*/
		public function setup ($params, $table, $condition = NULL) {
		    $this->params = $params;
		    $this->table = $table;
			$this->condition = $condition;
			return $this;
		}
		/*
		*@function add_condition
		*@param string $param
		*@param string|integer|float|null $value
		*@param object|string|null $oper
		*@return object
		*/
        public function add_condition ($param, $value, $oper = NULL) {
		    if (!is_object($oper)) {
		        $oper = new DB_Operator($oper);
			}
		    if (isset($this->condition)) {
			    $this->condition->add_cond($param, new DB_Condition_Value($value), $oper);
			}
			else {
			    $this->condition = new DB_Condition($param, new DB_Condition_Value($value), $oper);
			}
			return $this;
		}
		/*
		*@function quote_params
		*@param array $params
		*@return array
		*/
		protected function quote_params($params) {
		    for ($i = 0; $i < count($params); $i ++) {
			    $params[$i] = ($params[$i] == '*')? $params[$i] : '`'.$params[$i].'`';
			}
			return $params;
		}
		
	}
	/*
	*
    * DB_Query_SELECT: MySQL Query "SELECT" object
	* 
	*/
	class DB_Query_SELECT extends DB_Query {
	    /*
		* Object, represented ORDER BY or GROUP BY ability
		*@var object
		*/
	    private $grouping;
		/*
		* Object, represented LIMIT ability
		*@var object
		*/
		private $limiting;
		const class_name = 'DB_Query_SELECT';
		/*
		*@function set_group_by
		*@param string $field
		*@param string $direction
		*@return object
		*/
		public function set_group_by ($field, $direction = DB_Grouping::G_DESC) {
		    $this->grouping = new DB_Grouping(DB_Grouping::G_GROUP_BY, $field, $direction);
			return $this;
		}
		/*
		*@function set_order_by
		*@param string $field
		*@param string $direction
		*@return object
		*/
		public function set_order_by ($field, $direction = DB_Grouping::G_DESC) {
		    $this->grouping = new DB_Grouping(DB_Grouping::G_ORDER_BY, $field, $direction);
			return $this;
		}
		/*
		*@function set_limit
		*@param integer $from
		*@param integer $number
		*@return object
		*/
		public function set_limit ($from, $number) {
		    $this->limiting = new DB_Limiting($from, $number);
			return $this;
		}
		/*
		*@function to_string
		*@return string
		*/
		public function to_string() {
		    //return query's string representation...
		    $tail = (isset($this->condition))? "WHERE ".$this->condition->to_string() : "";
		    $tail .= (isset($this->grouping))? " ".$this->grouping->to_string() : "";
			$tail .= (isset($this->limiting))? " ".$this->limiting->to_string() : "";
		    return "SELECT ".implode(',', $this->quote_params($this->params))." FROM `".$this->table."` $tail";
		}
	}
	/*
	*
    * DB_Query_INSERT: MySQL Query "INSERT" object
	* 
	*/
	class DB_Query_INSERT extends DB_Query {
		const class_name = 'DB_Query_INSERT';
		/*
		*@function insert
		*@param array $params
		*@param string $table
		*@return integer
		*/
		public function insert($params, $table) {
		    $this->setup($params, $table);
		    $this->execute($this->db_handle);
			return mysql_insert_id($this->db_handle);
		}
		/*
		*@function to_string
		*@return string
		*/
		public function to_string() {
		    $vars = array();
			$values = array();
			foreach ($this->params as $var => $value) {
			    $vars[] = "`".$var."`";
				$values[] = "'".mysql_escape_string("$value")."'";
			}
		    return "INSERT INTO `".$this->table."` (".implode(',', $vars).") VALUES (".implode(',', $values).")";
		}
	}
	/*
	*
    * DB_Query_UPDATE: MySQL Query "UPDATE" object
	* 
	*/
	class DB_Query_UPDATE extends DB_Query_SELECT {
	    /*
		*@function update
		*@param array $params
		*@param string $table
		*@param object|null $condition
		*/
	    public function update($params, $table, $condition = NULL) {
		    $this->setup($params, $table, $condition);
		    $this->execute($this->db_handle);
		}
		/*
		*@function to_string
		*@return string
		*/
	    public function to_string() {
		    $params = "";
			foreach ($this->params as $param => $value) {
			    $params .= "`$param` = '".mysql_escape_string($value)."', ";
			}
			$params = str_replace(', |', '', $params.'|');
		    $tail = (isset($this->condition))? "WHERE ".$this->condition->to_string() : "";
		    return "UPDATE `".$this->table."` SET $params $tail";
		}    
	}
	/*
	*
    * DB_Query_DELETE: MySQL Query "DELETE" object
	* 
	*/
	class DB_Query_DELETE extends DB_Query {
	    /*
		*@function delete
		*@param string $table
		*@param object|null $condition
		*/
	    public function delete($table, $condition = NULL) {
		    $this->table = $table;
			$this->condition = $condition;
		    $this->execute($this->db_handle);
		}
		/*
		*@function to_string
		*@return string
		*/
	    public function to_string() {
		    $tail = (isset($this->condition))? "WHERE ".$this->condition->to_string() : "";
		    return "DELETE FROM `".$this->table."` $tail";
		}    
	}

    /*
	*
    * DB_Condition: MySQL condition (what's started after WHERE keyword) object
	* 
	*/
    class DB_Condition {
	    const DB_COND_AND = 'AND';
		const DB_COND_OR = 'OR';
		/*
		* MySQL condition object
		*@var object
		*/
		private $condition;
		/*
		* MySQL condition param
		*@var string
		*/
		private $param;
		/*
		* MySQL condition value
		*@var object|string|integer|float
		*/
		private $value;
		/*
		* MySQL condition operator
		*@var object|null
		*/
		private $operator;
		/*
		* MySQL conditions glue
		*@var string
		*/
		private $glue;
		/*
		* Constructor
		*@param string $param
		*@param object|string|integer|float $value
		*@param object|null $oper
		*@param string $glue
		*/
	    function __construct($param, $value, $oper = NULL, $glue = DB_Condition::DB_COND_AND) {
		    $this->param = $param;
	        $this->value =  (is_object($value))? $value : new DB_Condition_Value($value);
			$this->operator = (isset($oper))? $oper : new DB_Operator();
			$this->glue = $glue;
		}
		/*
		* Destructor
		*/
		function __destruct() {
		    unset($this->condition);
			unset($this->operator);
			unset($this->value);
		}
		/*
		*@function add_value
		*@param object|string|integer|float $value
		*/
		public function add_value ($value) {
		    $this->value = value;
		}
		/*
		*@function add_cond
		*@param string $param
		*@param object|string|integer|float $value
		*@param object $oper
		*@param string $glue
		*/
		public function add_cond ($param, $value, $oper, $glue = DB_Condition::DB_COND_AND) {
		    if (isset($this->condition)) {
                $this->_add_cond($this->condition->condition, array($param, $value, $oper, $glue));
			}
			else {
			    $this->condition = new DB_Condition($param, $value, $oper, $glue);
			}
		} 
		/*
		*@function to_string
		*@return string
		*/
		public function to_string() {
		    $cond = $this;
			$str = "";
		    $glue = "";
			while(isset($cond)) {
			    $str .= " $glue `".$cond->param."` ".$cond->operator->to_string()." ".$cond->value->to_string();
				$glue = $cond->glue;
				$cond = $cond->condition;
			}
			return $str;
		}
		
		/*
		*@function _add_cond
		*@param object $condition
		*@param array $cond
		*/
		private function _add_cond(DB_Condition $condition, $cond) {
		    if (isset($condition)) {
			    $this->_add_cond($condition->condition, $cond);
			}
			else {
			    $condition = new DB_Condition ($cond[0], $cond[1], $cond[2], $cond[3]);
			}
		}
		
	}
	
    /*
	*
    * DB_Operator: MySQL operator (what's used in MySQL conditions) object
	* 
	*/
	class DB_Operator {
		const DB_OPER_NOT = 'NOT';    
		const DB_OPER_IN = 'IN';
		const DB_OPER_LIKE = 'LIKE';
		const DB_OPER_EQ = '=';
		const DB_OPER_GE = '>=';
		const DB_OPER_LE = '<=';
		const DB_OPER_G = '>';
		const DB_OPER_L = '<';
	    const DB_OPER_NOT_EQ = '!=';
		/*
		* Operator name
		*@var string
		*/
		var $name;
		/*
		* Change result of logic operators
		*@var integer
		*/
		var $convertion;
		/*
		* Constructor
		*@param string $oper_name
		*@param integer $oper_convertion
		*/
		function __construct ($oper_name = DB_Operator::DB_OPER_EQ, $oper_convertion = 0) {
		    $operators = array(
			    DB_Operator::DB_OPER_NOT, 
				DB_Operator::DB_OPER_IN, 
				DB_Operator::DB_OPER_LIKE, 
				DB_Operator::DB_OPER_EQ, 
				DB_Operator::DB_OPER_GE,
				DB_Operator::DB_OPER_LE,
				DB_Operator::DB_OPER_G,
				DB_Operator::DB_OPER_L,
				DB_Operator::DB_OPER_NOT_EQ
			);
	        $this->name = (in_array($oper_name, $operators))? $oper_name : DB_Operator::DB_OPER_EQ;
			$this->convertion = $oper_convertion;
		}
		/*
		*@function to_string
		*@return string
		*/
		public function to_string() {
		    return (($this->convertion)? DB_Operator::DB_OPER_NOT." " : "").$this->name;
		}
	}
	
    /*
	*
    * DB_Condition_value: MySQL value object
	* 
	*/
    class DB_Condition_value {
	    /*
		* MySQL value
		*@var object|string|integer|float
		*/
		var $value;
		/*
		* Constructor
		*@param object|string|integer|float $value
		*/
		function __construct($value) {
		    $this->value = $value;
		}
		/*
		*@function to_string
		*@return string
		*/
		public function to_string() {
		    if (is_object($this->value)){
			    if (DB_Query_SELECT::class_name == get_class($this->value)) {
				    return "(".$this->value->to_string().")";
				}
			}
			else {
			    return "'".$this->value."'";
			}
		}
	}

    /*
	*
    * DB_Grouping: MySQL ORDER BY or GROUP BY ability
	* 
	*/
    class DB_Grouping {
	    const G_GROUP_BY = 'GROUP BY';
		const G_ORDER_BY = 'ORDER BY';
		const G_DESC = 'DESC';
		const G_ASC = 'ASC';
		/*
		* ORDER or GROUP BY?
		*@var string
		*/
		var $grouping;
		/*
		* Grouping field
		*@var string
		*/
		var $field;
		/*
		* Direction of soring: ASC or DESC
		*@var string
		*/
		var $direction;
		/*
		* Constructor
		*@param string $grouping_name
		*@param string $field_name
		*@param string $grouping_direction
		*/
		function __construct ($grouping_name = DB_Grouping::G_ORDER_BY, $field_name, $grouping_direction = DB_Grouping::G_DESC) {
		    $gs = array(
			    DB_Grouping::G_GROUP_BY, 
				DB_Grouping::G_ORDER_BY
			);
	        $this->grouping = (in_array($grouping_name, $gs))? $grouping_name : DB_Grouping::G_ORDER_BY;
			$this->field = $field_name;
			$this->direction = $grouping_direction;
		}
		/*
		*@function to_string
		*@return string
		*/
		public function to_string () {
		    return $this->grouping." `".$this->field."` ".$this->direction;
		}
		
	}
	
    /*
	*
    * DB_Limiting: MySQL LIMIT ability
	* 
	*/
    class DB_Limiting {
	    /*
		* "From row" index
		*@var integer
		*/
		var $from;
		/*
		* Count of rows to be gotten...
		*@var integer
		*/
		var $number;
		/*
		* Constructor
		*@param integer $from
		*@param integer $number
		*/
		function __construct ($from = 0, $number = 0) {
	        $this->from = $from;
			$this->number = $number;
		}
		/*
		*@function to_string
		*@return string
		*/
		public function to_string () {
		    return ($from)? "LIMIT $from".(($number)? ", $number" : "") : "";
		}
		
	}
?>