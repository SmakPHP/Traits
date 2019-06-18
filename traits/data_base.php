<?php

// Main namespace
namespace traits;

/**
 * Class for working with the database
 *
 * Additionally:
 * http://php.net/manual/ru/book.mysqli.php
 *
 * Usage example:

	// Library connection
	require_once "traits/register.php";

	// Initialization of the class of interaction with the database
	$db = new traits\data_base("test", "password");
	$data = $db->query("SELECT * FROM table");

 */

/**
 * Class data_base
 */
class data_base extends \mysqli {

	/**
	 * Query time
	 * @var int
	 */
	public $time_query = 0;

	/**
	 * Data acquisition time
	 * @var int
	 */
	public $time_taken = 0;

	/**
	 * Query counter
	 * @var int
	 */
	public $query_num = 0;

	/**
	 * List of executed requests
	 * @var array
	 */
	public $query_list = array();

	/**
	 * MySQLi constructor.
	 * @param $hostname
	 * @param $username
	 * @param $password
	 * @param $database
	 * @param string $port
	 */
	public function __construct($username, $password, $database = "", $hostname = "localhost", $port = "3306") {
		if ($database == "") $database = $username;
		@parent::__construct($hostname, $username, $password, $database, $port);
		if ($this->connect_errno) {
			$this->show_error("Could not connect to mysqli server!", 0);
		}
		$this->set_charset("utf8");
	}

	/**
	 * Database request
     * @param string $sql
     * @param bool $multi
     * @param array $ignore
     * @return \stdClass
     */
	public function query($sql, $multi = true, $ignore = array(1062)) {
		$before = $this->get_time();
		$query = parent::query($sql);
		$this->time_query += ($time_query = $this->get_time() - $before);
		$this->query_num++;
		if (!$this->errno) {
			$time_taken = 0; $result = new \stdClass();
			if ($query instanceof \mysqli_result) {
				$before = $this->get_time();
				$data = array();
				while ($row = $query->fetch_assoc()) {
					$data[] = $row;
					if (!$multi) break;
				}
				$result->num_rows = $query->num_rows;
				$result->row = isset($data[0]) ? $data[0] : array();
				$result->rows = $data;
				$query->free_result();
				$this->time_taken += ($time_taken = $this->get_time() - $before);
			} else {
				$result->num_rows = 0;
				$result->row = $result->rows = array();
			}
			$this->query_list[] = array(
				"query" => $sql,
				"time_query" => $time_query,
				"time_taken" =>  $time_taken
			);
			return $result;
		} else if (!in_array($this->errno, $ignore)) {
		    $this->show_error($this->error, $this->errno, $sql);
        }
	}

	/**
	 * Current timestamp
	 * @return float
	 */
	function get_time() {
		list($seconds, $micro) = explode(" ", microtime());
		return ((float)$seconds + (float)$micro);
	}

	/**
	 * Screening data
	 * @param $value
	 * @param string $type
	 * @param array $options
	 * @return false|float|int|string
	 */
	public function safe($value, $type = "default", $options = array(15, 4)) {
		if (is_float($value)) $result = (float)$value;
		else if (is_numeric($value)) $result = (int)$value;
		else $result = parent::real_escape_string($value);
		switch ($type) {
			case "ip":
				if (!preg_match("#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$#", $result)) {
					$result = "0.0.0.0";
				}
				break;
			case "decimal":
				$length = intval($options[0] - $options[1]);
				$part = intval($options[1]);
				$full = $length + 1;
				if (!preg_match("#^\d{1,".$length."}\.\d{1,".$part."}$#", $result)) {
					$result = number_format($result, $part, ".", "");
					if (strlen($result) > $full) $this->show_error(
						"Overflow decimal: ".$result.", format: (".$options[0].",". $options[1].")"
					);
				}
				break;
			case "date":
				if (!preg_match("#^\d{4,4}\-\d{2,2}\-\d{2,2}$#", $result)) {
					$result = date("Y-m-d", strtotime($result));
				}
				break;
			case "int":
				if (!is_numeric($result)) $result = (int)$result;
				break;
			case "float":
				if (!is_float($result)) $result = (float)$result;
				break;
			default:
				break;
		}
		return $result;
	}

	/**
	 * Perform table import
	 * @param $load
	 */
	public function import($load) {
		$blocks = explode(";", $load);
		foreach ($blocks as $block) {
			$sql = trim($block); if ($sql != "") $this->query($sql);
		}
	}

	/**
	 * Execute query with blocking
	 * @param $sql
	 * @param $table
	 */
	public function query_lock($sql, $table) {
		$this->query("LOCK TABLES `".$table."` WRITE");
		$this->query($sql);
		$this->query("UNLOCK TABLES");
	}

	/**
	 * Error output
	 * @param $error
	 * @param $num
	 * @param string $query
	 */
	function show_error($error, $num = "", $query = "")	{
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		if ($trace[1]["function"] == "query") $level = 1; else $level = 0;
		$trace[$level]["file"] = str_replace(root, "", $trace[$level]["file"]);
		$ip = $_SERVER["REMOTE_ADDR"];
		if (preg_match("#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$#", $ip)) {
			list($seconds, $micro) = explode(" ", microtime());
			$file = $ip." ".date("Y-m-d H:i:s").".".$micro.".error.sql";
			file_put_contents(data."/".$file,
				"File: ".$trace[$level]["file"]." Line: ".$trace[$level]["line"]."\r\n".
				"Error: ".$error."\r\nQuery: ".$query
			);
		}
		$query = htmlspecialchars($query, ENT_QUOTES, "ISO-8859-1");
		$error = htmlspecialchars($error, ENT_QUOTES, "ISO-8859-1");
		echo str_replace(
			array("{file}", "{line}", "{num}", "{error}", "{query}"),
			array($trace[$level]["file"], $trace[$level]["line"], $num, $error, $query),
			file_get_contents(dirname(__DIR__)."/template/db.html")
		);
		die();
	}

}