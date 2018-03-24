<?php

// Основное пространство имен
namespace traits;

/**
 * Класс для работы с базой данных
 *
 * Дополнительно:
 * http://php.net/manual/ru/book.mysqli.php
 *
 * Пример использования:

	// Подключение библиотеки
	require_once "traits/register.php";

	// Инициализация класса взаимодействия с базой данных
	$db = new traits\data_base("test", "password");
	// Выполняем запрос
	$data = $db->query("SELECT * FROM table");

 */

/**
 * Class data_base
 */
class data_base extends \mysqli {

	/**
	 * Время выполнения запросов
	 * @var int
	 */
	public $time_query = 0;

	/**
	 * Время получения данных
	 * @var int
	 */
	public $time_taken = 0;

	/**
	 * Счетчик запросов
	 * @var int
	 */
	public $query_num = 0;

	/**
	 * Список выполненных запросов и времени
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
		// Если не установлена база данных, берем из логина
		if ($database == "") $database = $username;
		// Подключение к базе данных
		@parent::__construct($hostname, $username, $password, $database, $port);
		// Если не удалось подключиттся
		if ($this->connect_errno) {
			// Выводим ошибку и прерываем выполнение
			$this->show_error("Could not connect to mysqli server!", 0);
		}
		// Установка кодировки
		$this->set_charset("utf8");
	}

	/**
	 * Запрос к базе данных
	 * @param string $sql
	 * @param bool $multi
	 * @return bool|\stdClass
	 */
	public function query($sql, $multi = true) {
		// Засекаем время на запрос
		$before = $this->get_time();
		// Выполнение запроса
		$query = parent::query($sql);
		// Подсчитываем время на запрос
		$this->time_query += ($time_query = $this->get_time() - $before);
		// Количество запросов
		$this->query_num++;
		// Если запрос успешно выполнился
		if (!$this->errno) {
			// Инициализация времени выполнения и результата
			$time_taken = 0; $result = new \stdClass();
			// Если есть результат
			if ($query instanceof \mysqli_result) {
				// Засекаем время на получение данных
				$before = $this->get_time();
				// Инициализация массива с полученными данными
				$data = array();
				// Получение данных
				while ($row = $query->fetch_assoc()) {
					// Установка данных
					$data[] = $row;
					// Если не мульти режим - прерываем
					if (!$multi) break;
				}
				// Установка данных в стандартный класс
				$result->num_rows = $query->num_rows;
				$result->row = isset($data[0]) ? $data[0] : array();
				$result->rows = $data;
				// Освобождение результатов запроса
				$query->free_result();
				// Подсчитываем время получения данных
				$this->time_taken += ($time_taken = $this->get_time() - $before);
			// Иначе нулевые значения
			} else {
				// Установка нулевых значений
				$result->num_rows = 0;
				$result->row = $result->rows = array();
			}
			// История запросов
			$this->query_list[] = array(
				"query" => $sql,
				"time_query" => $time_query,
				"time_taken" =>  $time_taken
			);
			// Вывод данных
			return $result;
			// Иначе выводим ошибку и прерываем выполнение
		} else $this->show_error($this->error,	$this->errno,	$sql);
	}

	/**
	 * Текущая метка времени с миллисекундами
	 * @return float
	 */
	function get_time() {
		list($seconds, $micro) = explode(" ", microtime());
		return ((float)$seconds + (float)$micro);
	}

	/**
	 * Экранирование данных
	 * @param $value
	 * @param string $type
	 * @param array $options
	 * @return false|float|int|string
	 */
	public function safe($value, $type = "default", $options = array(15, 4)) {
		// Возвращаем дробное число
		if (is_float($value)) $result = (float)$value;
		// Возвращаем просто число
		else if (is_numeric($value)) $result = (int)$value;
		// Иначе зкранирование как строки
		else $result = parent::real_escape_string($value);
		// Приведение к типу
		switch ($type) {
			// Если ip адрес
			case "ip":
				// Если не корректный ip адрес
				if (!preg_match("#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$#", $result)) {
					// Устанавливаем по умолчанию
					$result = "0.0.0.0";
				}
				break;
			// Если десятичное число
			case "decimal":
				// Длина целого числа
				$length = intval($options[0] - $options[1]);
				// Длина дробного числа
				$part = intval($options[1]);
				// Общая длина
				$full = $length + 1;
				// Если не корректное десятичное число
				if (!preg_match("#^\d{1,".$length."}\.\d{1,".$part."}$#", $result)) {
					// Приводим к нужному формату
					$result = number_format($result, $part, ".", "");
					// Иначе выводим ошибку и прерываем выполнение
					if (strlen($result) > $full) $this->show_error(
						"Overflow decimal: ".$result.", format: (".$options[0].",". $options[1].")"
					);
				}
				break;
			// Если приведение к дате
			case "date":
				// Если не корректная дата
				if (!preg_match("#^\d{4,4}\-\d{2,2}\-\d{2,2}$#", $result)) {
					// Приводим к нужному формату
					$result = date("Y-m-d", strtotime($result));
				}
				break;
			// Если приведение к числу
			case "int":
				// Если не число то приводим к числу
				if (!is_numeric($result)) $result = (int)$result;
				break;
			// Если приведение к дробному
			case "float":
				// Если не дробное то делаем дробным
				if (!is_float($result)) $result = (float)$result;
				break;
			// Оставляем как есть
			default:
				break;
		}
		// Вывод результата
		return $result;
	}

	/**
	 * Выполнение импорта таблиц
	 * @param $load
	 */
	public function import($load) {
		// Разбиваем на запросы
		$blocks = explode(";", $load);
		// Перебираем запросы
		foreach ($blocks as $block) {
			// Если не пустой, то - выполняем
			$sql = trim($block); if ($sql != "") $this->query($sql);
		}
	}

	/**
	 * Выполнение запроса с блокировкой
	 * @param $sql
	 * @param $table
	 */
	public function query_lock($sql, $table) {
		// Блокируем нужную таблицу
		$this->query("LOCK TABLES `".$table."` WRITE");
		// Выполняем запрос
		$this->query($sql);
		// Снимаем блокировку со всех таблиц
		$this->query("UNLOCK TABLES");
	}

	/**
	 * Вывод ошибки
	 * @param $error
	 * @param $num
	 * @param string $query
	 */
	function show_error($error, $num = "", $query = "")	{
		// Трассировка к файлу
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		// Исключаем вывод из трассировки последней функции
		if ($trace[1]["function"] == "query") $level = 1; else $level = 0;
		// Удаляем путь к корню
		$trace[$level]["file"] = str_replace(root, "", $trace[$level]["file"]);
		// IP клиента
		$ip = $_SERVER["REMOTE_ADDR"];
		// Если установлена отладка и валидный ip адрес
		if (preg_match("#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$#", $ip)) {
			// Получение метки времени
			list ($seconds, $micro) = explode(" ", microtime());
			// Название файла с запросами
			$file = $ip." ".date("Y-m-d H:i:s").".".$micro.".error.sql";
			// Запись запросов в файл
			file_put_contents(data."/".$file,
				"File: ".$trace[$level]["file"]." Line: ".$trace[$level]["line"]."\r\n".
				"Error: ".$error."\r\nQuery: ".$query
			);
		}
		// Экранирование специальных символов
		$query = htmlspecialchars($query, ENT_QUOTES, "ISO-8859-1");
		$error = htmlspecialchars($error, ENT_QUOTES, "ISO-8859-1");
		// Вывод ошибки
		echo str_replace(
			array("{file}", "{line}", "{num}", "{error}", "{query}"),
			array($trace[$level]["file"], $trace[$level]["line"], $num, $error, $query),
			file_get_contents(root."/template/db.html")
		);
		// Прерываем выполнение
		die();
	}

}