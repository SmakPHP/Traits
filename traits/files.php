<?php

// Основное пространство имен
namespace traits;

/**
 * Класс для работы с файлами
 */
class files {

	/**
	 * Ищет путь к директории с заданным именем из исходного
	 * @param $path
	 * @param $name
	 * @return mixed
	 */
	public static function search($path, $name) {
		// Текущая директория
		$dir = dirname($path);
		// Если дошли до главной возвращаем просто главную
		if ($dir == '.') return '/';
		// Если дошли до заданной то возвращаем путь к ней
		else if (basename($dir) == $name) return $dir;
		// Иначе делеаем рекрсивный поиск
		else return self::search($dir, $name);
	}

	/**
	 * Чтение файла
	 * @param string $path Путь к файлу
	 * @param int $start Читать с позиции
	 * @param int $len Длиной
	 * @param bool $stop Флаг остановки
	 * @param bool $alert Выводить сообщение
	 * @return bool|string
	 */
	public static function read_file($path, $start = 0, $len = 0, $stop = true, $alert = true) {
		// Если не установлена длина - читаем файл целиком
		if (!$len) $data = @file_get_contents($path);
		// Иначе читаем часть файла
		else $data = @file_get_contents($path, null, null, $start, $len);
		// Если произошла ошибка при открытии файла
		if ($data === false) {
			// Выводим ошибку если установлен вывод предупреждений
			if ($alert) show::alert("Не удалось открыть файл: ".$path, $stop);
		}
		// Выводим результат
		return $data;
	}

	/**
	 * Запись файла
	 * @param string $path Путь к файлу
	 * @param string $data Данные для записи
	 * @param bool $stop Остановить в случае ошибки
	 * @param bool $alert Выводить сообщение
	 */
	public static function write_file($path, $data, $stop = true, $alert = true) {
		// Запись данных в файл
		if (@file_put_contents($path, $data) === false) {
			// Выводим ошибку если установлен вывод предупреждений
			if ($alert) show::alert("Не удалось записать файл: ".$path, $stop);
		}
	}

	/**
	 * Добавить строку к файлу
	 * @param string $path Путь к файлу
	 * @param string $data Данные для добавления
	 * @param bool $stop Остановить в случае ошибки
	 * @param string $new Символ новый строки
	 * @param bool $alert Выводить сообщение
	 */
	public static function add_file($path, $data, $stop = true, $new = "\r\n", $alert = true) {
		// Запись данных в файл
		if (@file_put_contents($path, $data.$new, FILE_APPEND) === false) {
			// Выводим ошибку если установлен вывод предупреждений
			if ($alert) show::alert("Не удалось добавить в файл: ".$path, $stop);
		}
	}

	/**
	 * Перемещение файла
	 * http://php.net/manual/ru/function.fopen.php
	 * @param string $from Откуда
	 * @param string $to Куда
	 * @param bool $delete Флаг удаления
	 */
	public static function move_file($from, $to, $delete = true) {
		// Открываем файл для чтения
		$read = fopen($from, "r");
		// Если ошибка при открытии
		if (!$read) show::alert("Не удалось открыть файл: ".$from);
		// Открываем файл для записи
		$write = fopen($to, "a");
		// Если ошибка при открытии
		if (!$write) show::alert("Не удалось открыть файл: ".$to);
		// Читаем по блочно
		while (!feof($read)) {
			// Читаем часть
			$part = fread($read, 8192);
			// Записываем часть
			fwrite($write, $part);
		}
		// Закрываем файлы
		fclose($read); fclose($write);
		// Удаляем исходный
		if ($delete) unlink($from);
	}

	/**
	 * Перемещение директории
	 * @param string $from Директория источник
	 * @param string $to Директория назначения
	 * @param bool $delete Флаг удаления
	 */
	public static function move_dir($from, $to, $delete = true) {
		// Создание подпапок
		if (!file_exists($to)) mkdir($to, 0777, true);
		// Выполняем только для папок
		if (is_dir($from) && is_dir($to)) {
			// Открываем папку
			foreach (scandir($from) as $name) {
				// Считаем количество папок
				if ($name == "." || $name == "..") continue;
				// Путь к файлу/папке
				$path = $from."/".$name;
				// Если файл
				if (is_file($path)) {
					// Перемещаем файл с удалением
					self::move_file($path, $to."/".$name, $delete);
				}
			}
			// Удаляем директорию
			if ($delete) rmdir($from);
		}
	}

	/**
	 * Удаление файлов из директории
	 * @param string $dir Путь к директории
	 * @param bool $recursion Удалять вложенные папки
	 * @param bool $self Удалить текущую папку
	 */
	public static function clear_dir($dir, $recursion = true, $self = false) {
		// Открываем папку
		foreach (scandir($dir) as $name) {
			// Считаем количество папок
			if ($name == '.' || $name == '..') continue;
			// Путь к файлу
			$path = $dir.'/'.$name;
			// Если директория
			if (is_dir($path) && $recursion) {
				// Выполняем рекурсивную очистку
				self::clear_dir($path, true);
				// Удаляем директорию
				rmdir($path);
				// Иначе если файл то удаляем
			} else if (is_file($path)) unlink($path);
		}
		// Если удалить текущую папку
		if ($self) rmdir($dir);
	}

	/**
	 * Функция поиска файла в директории
	 * @param string $from Директория в которой будет производится поиск
	 * @param string $files Список найденных файлов
	 * @param string $find Маска для поиска (регулярное выражение)
	 * @param string $plus Дополнительная маска для поиска или исключения (регулярное выражение)
	 * @param bool $exclude Флаг для лполнительной маски
	 */
	public static function search_file($from, &$files, $find = "#r\-\w+#",
																		 $plus = "#p\-50000|r\-0#", $exclude = false) {
		// Выполняем только для папок
		if (is_dir($from)) {
			// Инициализируем если надо массив
			if (!isset($files)) $files = array();
			// Открываем папку
			foreach (scandir($from) as $name) {
				// Считаем количество папок
				if ($name == "." || $name == "..") continue;
				// Путь к файлу/папке
				$path = $from."/".$name;
				// Если файл
				if (is_file($path)) {
					// Если установлен критерий поиска
					if (strlen($find)) {
						// Если имя файла соотвествует поиску
						if (preg_match($find, $name)) {
							// Если установлен критерий исключения
							if (strlen($plus)) {
								// Если соотвествует дополнительному условию с флагом
								if ($exclude xor preg_match($plus, $name)) $files[] = $path;
							// Иначе добавлем все файлы
							} else $files[] = $path;
						}
					// Иначе добавлем все файлы
					} else $files[] = $path;
				// Выполняем рекурсию, если установлен флаг
				} else self::search_file($path, $files, $find, $plus, $exclude);
			}
		}
	}

	/**
	 * Функция получения последней строки в файле
	 * @param string $name Имя файла
	 * @param int $count Количество
	 * @return bool|string
	 */
	public static function read_last($name, $count = 1) {
		// Открываем файл для чтения
		$result = ""; $file = fopen($name, "r"); if ($file) {
			// Перемещаем курсор в конец файла
			if (fseek($file, -1, SEEK_END) == 0) { $pos = ftell($file);
				// Посимвольно считываем с конца и не более 5000 символов
				for ($i = $pos; $i > ($pos - 5000) && $i > 0; $i--)
					// Если дошли до первого символа в файле
					if ($i == 1) fseek($file, -1, SEEK_CUR); else {
						// Откатываем на два сивола назад
						fseek($file, -2, SEEK_CUR);
						// Если перевод строки
						if (fread($file, 1) == "\n") {
							// и достигнуто заданное кол-во строк - прерываем
							$count--; if ($count <= 0) break;
						}
					}
				// Считываем полученный блок
				$result = fread($file, $pos - $i + 1);
			// Закрываем файл
			} fclose($file);
		// Выводим результат
		} return $result;
	}

}