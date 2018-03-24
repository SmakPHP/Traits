<?php

// Основное пространство имен
namespace traits;

/**
 * Класс быстрого шаблонизатора
 *
 * Пример использования:

	$tp = new traits\template("index.html");
	$tp->set("body", "text");
	echo $tp->result;

 */
class template {

	/**
	 * Текст шаблона
	 * @var bool|string
	 */
	public $result = "";

	/**
	 * Добавляем указание пути к шаблону
	 * @param $path
	 */
	public function __construct($path) {
		// Если установлен путь к шаблону
		if (strlen($path)) {
			// Загрузка шаблона
			$this->result = files::read_file($path);
			// Иначе - выводим ошибку
		} else show::alert("Необходимо установить путь к шаблону", true);
	}

	/**
	 * Установка значения
	 * @param $name
	 * @param $value
	 */
	public function set($name, $value) {
		// Установка данных
		$this->result = str_replace("{".$name."}", $value, $this->result);
	}

}