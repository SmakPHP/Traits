<?php

// Основное пространство имен
namespace traits;

/**
 * Класс вывода
 *
 * Пример использования:

	// Подключение библиотеки
	require_once 'traits/register.php';

	// Инициализация класса вывода
	$show = new traits\show(__DIR__);
	// Вывод сообщения
	traits\show::alert('Вывод сообщения');

 */
class show {

	/**
	 * Директория вывода
	 * @var string
	 */
	private static $path = null;

	/**
	 * Выполняемая процедура перед остановкой
	 * @var null
	 */
	private static $before = null;

	/**
	 * Конструктор класса
	 * @param string $path Директория вывода
	 */
	public function __construct($path) {
		// Установка директории вывода
		self::$path = is_dir($path) ? realpath($path) : null;
	}

	/**
	 * Установка процедуры перед остановкой
	 * @param $before
	 */
	public static function completion($before) {
		// Установка выполняемой проедуры
		self::$before = is_callable($before, true) ? $before : null;
	}

	/**
	 * Безопасный вывод сообщения
	 * @param string $message Текст сообщения
	 * @param bool $stop Остановить выполнение
	 * @param string $data Дополнительные данные
	 */
	public static function alert($message, $stop = false, $data = '') {
		// Выводим сообщение
		echo '<pre>'.htmlspecialchars($message).'</pre>';
		// Если установлена директория для логирования
		if (!is_null(self::$path)) {
			// Записываем данные в лог файл
			file_put_contents(self::$path.'/message.log', date("Y-m-d H:i:s")." - ".$message."\r\n", FILE_APPEND);
			// Если установлены данные
			if (strlen($data)) {
				// Генерируем название файла для данных
				$file = "debug.".date("Y-m-d_H-i-s").'.'.tools::random(7, '0123456789').".log";
				// Записываем данные в файл
				file_put_contents(self::$path.'/'.$file, $data);
				// Сохранение сообщение в лог
				self::alert("Данные, см.: ".$file, $stop);
			}
		}
		// Если установлен флаг остаановки
		if ($stop) {
			// Если установлена процедура то выполняем
			if (!is_null(self::$before)) call_user_func(self::$before);
			// Прерывваем выполнение
			die();
		}
	}

}
