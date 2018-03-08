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
	 * Удаление файлов из директории
	 * @param string $dir Путь к директории
	 * @param bool $recursion Удалять вложенные папки
	 */
	public static function clear($dir, $recursion = true) {
		// Открываем папку
		foreach (scandir($dir) as $name) {
			// Считаем количество папок
			if ($name == '.' || $name == '..') continue;
			// Путь к файлу
			$path = $dir.'/'.$name;
			// Если директория
			if (is_dir($path) && $recursion) {
				// Выполняем рекурсивную очистку
				self::clear($path, true);
				// Удаляем директорию
				rmdir($path);
				// Иначе если файл то удаляем
			} else if (is_file($path)) unlink($path);
		}
	}

}