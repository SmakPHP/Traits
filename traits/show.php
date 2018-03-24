<?php

// Основное пространство имен
namespace traits;

/**
 * Класс вывода
 *
 * Пример использования:

	// Подключение библиотеки
	require_once "traits/register.php";

	// Инициализация класса вывода
	$show = new traits\show(__DIR__);
	// Вывод сообщения
	traits\show::alert("Вывод сообщения");

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
	 * Запуск из командной строки
	 * @var bool
	 */
	private static $shell = false;

	/**
	 * Конструктор класса
	 * @param string $path Директория вывода
	 */
	public function __construct($path) {
		// Установка директории вывода
		self::$path = is_dir($path) ? realpath($path) : null;
		// Установка флага запуска из командной строки
		self::$shell = isset($_SERVER['SHELL']);
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
	 * @param string $name Название лог файла
	 */
	public static function alert($message, $stop = false, $data = "", $name = "message.log") {
		// Если запуск из командной строки то выводим как есть
		if (self::$shell) echo $message."\r\n";
		// Иначе выводим сообщение в виде html строки
		else echo "<pre>".htmlspecialchars($message)."</pre>";
		// Если установлена директория для логирования
		if (!is_null(self::$path)) {
			// Записываем данные в лог файл
			file_put_contents(self::$path.$name, date("Y-m-d H:i:s")." - ".$message."\r\n", FILE_APPEND);
			// Если установлены данные
			if (strlen($data)) {
				// Генерируем название файла для данных
				$file = "debug.".date("Y-m-d_H-i-s").'.'.tools::random(7, "0123456789").".log";
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

	/**
	 * Генерация постраничной навигации
	 * @param int $count Всего страниц
	 * @param int $select Номер активной страницы
	 * @return array
	 */
	public static function paginator($count, $select) {
		// Инициализация
		$result = array();
		// Если страниц 0 или 1, вернем пустой массив (переключатели не выводятся)
		if ($count == 0 || $count == 1) return $result;
		// Если страниц больше 10, заполним массив переключателями в зависимости от активной страницы
		if ($count > 10) {
			// Если активная страница - одна из первых или одна из последних страниц
			if ($select <= 4 || $select + 3 >= $count) {
				for($i = 0; $i <= 4; $i++) {
					$result[$i] = $i + 1;
				}
				$result[5] = "...";
				for($j = 6, $k = 4; $j <= 10; $j++, $k--) {
					$result[$j] = $count - $k;
				}
				// В противном случае в массив запишем первые и последние две страницы
			} else {
				$result[0] = 1;
				$result[1] = 2;
				$result[2] = "...";
				$result[3] = $select - 2;
				$result[4] = $select - 1;
				$result[5] = $select;
				$result[6] = $select + 1;
				$result[7] = $select + 2;
				$result[8] = "...";
				$result[9] = $count - 1;
				$result[10] = $count;
			}
			// Если страниц меньше 10, заполним массив переключателей всеми страницами
		} else {
			for($n = 0; $n < $count; $n++) {
				$result[$n] = $n + 1;
			}
		}
		return $result;
	}

	/**
	 * Функция транслита
	 * @param string $sourse Исходная строка
	 * @param bool $lower Преобразовать к нижнему регистру
	 * @param bool $dot Удалить точки
	 * @return mixed|string
	 */
	function translit($sourse, $lower = true, $dot = true) {
		// Список символов для преобразования
		$lang = array(
			"а" => "a",  "б" => "b",  "в" => "v",   "г" => "g",  "д" => "d", "е" => "e",
			"ё" => "e",  "ж" => "zh", "з" => "z",   "и" => "i",  "й" => "y", "к" => "k",
			"л" => "l",  "м" => "m",  "н" => "n",   "о" => "o",  "п" => "p", "р" => "r",
			"с" => "s",  "т" => "t",  "у" => "u",   "ф" => "f",  "х" => "h", "ц" => "c",
			"ч" => "ch", "ш" => "sh", "щ" => "sch", "ь" => "",   "ы" => "y", "ъ" => "",
			"э" => "e",  "ю" => "yu", "я" => "ya",  "ї" => "yi", "є" => "ye",
			"А" => "A",  "Б" => "B",  "В" => "V",   "Г" => "G",  "Д" => "D", "Е" => "E",
			"Ё" => "E",  "Ж" => "Zh", "З" => "Z",   "И" => "I",  "Й" => "Y", "К" => "K",
			"Л" => "L",  "М" => "M",  "Н" => "N",   "О" => "O",  "П" => "P", "Р" => "R",
			"С" => "S",  "Т" => "T",  "У" => "U",   "Ф" => "F",  "Х" => "H", "Ц" => "C",
			"Ч" => "Ch", "Ш" => "Sh", "Щ" => "Sch", "Ь" => "",   "Ы" => "Y", "Ъ" => "",
			"Э" => "E",  "Ю" => "Yu", "Я" => "Ya",  "Ї" => "yi", "Є" => "ye"
		);
		// Удаляем теги из строки
		$str = trim(strip_tags($sourse));
		// Заменяем пробельные и слеш на дифис
		$str = preg_replace("/\s+/ms", "-", $str);
		$str = str_replace("/", "-", $str);
		// Траслит
		$str = strtr($str, $lang);
		// Убираем все остальное
		$str = preg_replace( "/[^a-z0-9\_\-".($dot ? "." : "")."]+/mi", "", $str);
		// Оставляем один дифис или точку
		$str = preg_replace("#[\-]+#i", "-", $str);
		$str = preg_replace("#[.]+#i", ".", $str);
		// Преобразуем в нижний регистр, если установлено
		if ($lower) $str = strtolower($str);
		// Защита от инклуда
		$str = str_ireplace(".php", "", $str); $str = str_ireplace(".php", ".ppp", $str);
		// Вывод результата
		return $str;
	}

	/**
	 * Функция преобразование кириллицы в спец символы
	 * @param string $sourse
	 * @return string
	 */
	function cyrillic_encode($sourse) {
		// Если содержатся символы utf-8
		if (tools::is_utf8($sourse)) {
			// Просто устанавливаем исходную строку
			$str = $sourse;
			// Если не utf-8 и есть символы кириллицы
		} else if (preg_match("#[\xE0-\xFF\xC0-\xDF\xA8\xB8]+#", $sourse)) {
			// Преобразуем к utf-8
			$str = iconv("CP1251", "UTF-8", $sourse);
			// Иначе выводим без преобразования
		} else return $sourse;
		// Список символов для преобразования
		$lang = array(
			"а" => "%d0%b0", "б" => "%d0%b1", "в" => "%d0%b2", "г" => "%d0%b3",
			"д" => "%d0%b4", "е" => "%d0%b5", "ё" => "%d1%91", "ж" => "%d0%b6",
			"з" => "%d0%b7", "и" => "%d0%b8", "й" => "%d0%b9", "к" => "%d0%ba",
			"л" => "%d0%bb", "м" => "%d0%bc", "н" => "%d0%bd", "о" => "%d0%be",
			"п" => "%d0%bf", "р" => "%d1%80", "с" => "%d1%81", "т" => "%d1%82",
			"у" => "%d1%83", "ф" => "%d1%84", "х" => "%d1%85", "ц" => "%d1%86",
			"ч" => "%d1%87", "ш" => "%d1%88", "щ" => "%d1%89", "ь" => "%d1%8c",
			"ы" => "%d1%8b", "ъ" => "%d1%8a", "э" => "%d1%8d", "ю" => "%d1%8e",
			"я" => "%d1%8f",
			"А" => "%d0%90", "Б" => "%d0%91", "В" => "%d0%92", "Г" => "%d0%93",
			"Д" => "%d0%94", "Е" => "%d0%95", "Ё" => "%d0%81", "Ж" => "%d0%96",
			"З" => "%d0%97", "И" => "%d0%98", "Й" => "%d0%99", "К" => "%d0%9a",
			"Л" => "%d0%9b", "М" => "%d0%9c", "Н" => "%d0%9d", "О" => "%d0%9e",
			"П" => "%d0%9f", "Р" => "%d0%a0", "С" => "%d0%a1", "Т" => "%d0%a2",
			"У" => "%d0%a3", "Ф" => "%d0%a4", "Х" => "%d0%a5", "Ц" => "%d0%a6",
			"Ч" => "%d0%a7", "Ш" => "%d0%a8", "Щ" => "%d0%a9", "Ь" => "%d0%ac",
			"Ы" => "%d0%ab", "Ъ" => "%d0%aa", "Э" => "%d0%ad", "Ю" => "%d0%ae",
			"Я" => "%d0%af"
		);
		// Кодирование кириллицы
		$str = strtr($str, $lang);
		// Вывод результата
		return $str;
	}

	/**
	 * Сделать ссылку
	 * @param string $link Путь ссылки
	 * @param string $text Тест ссылки
	 * @param string $target Тип открытия
	 * @return string
	 */
	public static function build_link($link, $text = "", $target = " target='_blank'") {
		// Если текст ссылки не установлен
		if ($text == "") $info = $link; else $info = $text;
		// Выводим ссылку
		return "<a href='".$link."'".$target.">".$info."</a>";
	}

	/**
	 * Сделать уведомление
	 * @param $msg
	 * @return string
	 */
	public static function create_alert($msg) {
		// Добавляем сообщение
		return "<script>alert('".$msg."');<script>";
	}

}
