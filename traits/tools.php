<?php

// Основное пространство имен
namespace traits;

/**
 * Класс с дополнительными утилитами
 */
class tools {

	/**
	 * Функция поиска по регулярному выражению
	 *
	 * Модификаторы шаблонов
	 * http://php.net/manual/ru/reference.pcre.pattern.modifiers.php
	 *
	 * Синтаксис регулярных выражений
	 * http://php.net/manual/ru/reference.pcre.pattern.syntax.php
	 * http://php.net/manual/ru/regexp.reference.escape.php
	 *
	 * Флаги выборки множества:
	 *
	 * PREG_PATTERN_ORDER
	 * Если этот флаг установлен, результат будет упорядочен следующим образом: элемент $match[0]
	 * содержит массив полных вхождений шаблона, элемент $match[1] содержит массив вхождений первой подмаски, и так далее.
	 *
	 * PREG_SET_ORDER
	 * Если этот флаг установлен, результат будет упорядочен следующим образом: элемент $match[0]
	 * содержит первый набор вхождений, элемент $match[1] содержит второй набор вхождений, и так далее.
	 *
	 * @param string $reg Регулярное выражение для поиска
	 * @param string $data Данные в которых ведется поиск
	 * @param string $find Найденные данные
	 * @param bool $stop Прервать выполнение если не найдено
	 * @param int $pos Позиция в наденном массиве
	 * @return int
	 */
	public static function find($reg, $data, &$find, $stop = false, $pos = 1) {
		// Инициализация
		$find = '';
		// Поиск регулярного выражения
		if ($result = preg_match($reg, $data, $match)) $find = trim($match[$pos]);
		// Если не найдено и установлен флаг остановки - прерываем выполнение
		else if ($stop) show::alert('Не найдено регулярное выражение: '.$reg, $stop, $data);
		// Вывод результата
		return $result;
	}

	/**
	 * Генерация случайной строки
	 * @param int $length Длина случайной строки
	 * @param string $symbols Строка символовов из которой будут браться символы
	 * @return string
	 */
	public static function random($length = 5, $symbols = '0123456789abcdefghijklmnopqrstuvwxyz') {
		// Инициализация
		$result = '';
		// Длина строки с исходными символами
		$lengths = strlen($symbols);
		// Выполняем пока не достигнем заданного количества символов
		for ($i = 0; $i < $length; $i++) {
			// Добавляем случайный символ из строки
			$result .= $symbols[rand(0, $lengths - 1)];
		}
		// Вывод результата
		return $result;
	}

	/**
	 * Получение главного хоста
	 * @param string $sub Поддомен
	 * @return string
	 */
	public static function domain($sub) {
		// Возвращаем главный хост
		return implode('.', array_slice(explode('.', $sub), -2, 2));
	}

}
