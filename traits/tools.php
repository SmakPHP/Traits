<?php

// Основное пространство имен
namespace traits;

/**
 * Класс с дополнительными утилитами
 */
class tools {

	/**
	 * Чтение значения переменой
	 * @param string $name Название переменной
	 * @param string $value Значение переменной
	 * @param string $reg Фильтр (регулярное выражение)
	 * @return bool
	 */
	public static function read_get($name, &$value, $reg = "|^[A-z0-9\.\-]+$|") {
		// Инициализация переменных
		global $_GET; $value = "";
		// Проверяем значение переменной
		if (isset($_GET[$name]) && preg_match($reg, $_GET[$name])) {
			// Устанавливаем значение переменной и возвращаем успех
			$value = $_GET[$name]; return true;
		// Иначе - устанавливаем ошибку
		} else return false;
	}

	/**
	 * Чтение значения переменой
	 * @param string $name Название переменной
	 * @param string $value Значение переменной
	 * @param string $reg Фильтр (регулярное выражение)
	 * @return bool
	 */
	public static function read_post($name, &$value, $reg = "|^[A-z0-9\.\-]+$|") {
		// Инициализация переменных
		global $_POST; $value = "";
		// Проверяем значение переменной
		if (isset($_POST[$name]) && preg_match($reg, $_POST[$name])) {
			// Устанавливаем значение переменной и возвращаем успех
			$value = $_POST[$name]; return true;
		// Иначе - устанавливаем ошибку
		} else return false;
	}

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
	public static function find_reg($reg, $data, &$find, $stop = false, $pos = 1) {
		// Инициализация
		$find = "";
		// Поиск регулярного выражения
		if ($result = preg_match($reg, $data, $match)) $find = trim($match[$pos]);
		// Если не найдено и установлен флаг остановки - прерываем выполнение
		else if ($stop) show::alert("Не найдено регулярное выражение: ".$reg, $stop, $data);
		// Вывод результата
		return $result;
	}
	/**
	 * Функция поиска текста
	 * @param $data
	 * @param $search
	 * @param bool $stop
	 * @return bool
	 */
	public static function find_text($data, $search, $stop = false) {
		// Поиск текста
		$result = (strpos($data, $search) !== false);
		// Если не найдено и установлен флаг остановки - прерываем выполнение
		if (!$result && $stop) show::alert("Не найден текст: ".$search, $stop, $data);
		// Вывод результата
		return $result;
	}

	/**
	 * Функция поиска массива регулярных выражений
	 * @param array $reg Массив с регулярными выражениями
	 * @param string $data Данные для поиска
	 * @param bool $match Результат поиска
	 * @return bool
	 */
	public static function find_regs($reg, $data, &$match = false) {
		// Инициализация
		$match = false;
		// Поиск регулярного выражения
		foreach ($reg as $find) if (preg_match($find, $data, $match)) return true;
		// По умолчанию не найдено
		return false;
	}

	/**
	 * Функция замены только первого вхождения
	 * @param string $search Строка для поиска
	 * @param string $replace Строка для замены
	 * @param string $subject Данные
	 * @return mixed
	 */
	public static function replace_first($search, $replace, $subject) {
		// Ищем вхождение строки
		$pos = strpos($subject, $search);
		// Если строка найдена
		if ($pos !== false) {
			// Вставляем строку замены и возвращаем результат
			return substr_replace($subject, $replace, $pos, strlen($search));
			// Если не найдено возвращаем исходную строку
		} else return $subject;
	}

	/**
	 * Генерация случайной строки
	 * @param int $length Длина случайной строки
	 * @param string $symbols Строка символовов из которой будут браться символы
	 * @return string
	 */
	public static function random($length = 5, $symbols = "0123456789abcdefghijklmnopqrstuvwxyz") {
		// Инициализация
		$result = "";
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
		return implode(".", array_slice(explode(".", $sub), -2, 2));
	}

	/**
	 * Проверка кодировки
	 * @param string $string Текст для проверки
	 * @return bool
	 */
	public static function is_utf8($string) {
		return (bool)preg_match("//u", $string);
	}

	/**
	 * Функция декодирования из машинного представления
	 * @param string $text Текст в виде uD0B0uD0B1
	 * @return mixed
	 */
	public static function decode_utf8($text) {
		return preg_replace_callback("/\x75([0-9a-fA-F]{4})/", function ($match) {
			return mb_convert_encoding(pack("H*", $match[1]), "UTF-8", "UCS-2BE");
		}, $text);
	}

	/**
	 * Функция округления байт
	 * @param int $size Размер в байтах
	 * @return string
	 */
	public static function round_byte($size) {
		$list = array("b", "kb", "mb", "gb", "tb", "pb");
		return round($size/pow(1024, ($i = floor(log($size, 1024)))), 2)." ".$list[$i];
	}

	/**
	 * Функция преобразование числа в шестнадцатеричную систему
	 * @param int $value Исходное число
	 * @param int $length Длина шестнадцатеричного
	 * @return string
	 */
	public static function dec_hex($value, $length = 8) {
		// Преобразование в шестнадцатеричную систему
		$result = dechex($value);
		// Скользо символов необходимо дополнить
		$len = $length - strlen($result);
		// Если длина меньше добавляем нули
		if ($len > 0) $result = str_repeat(0, $len).$result;
		// Вывод результата
		return $result;
	}

	/**
	 * Функция создания кеш директории
	 * @param string $path Исходный путь
	 * @param string $value Шестнадцетирный путь
	 * @return string
	 */
	public static function path_cache($path, $value) {
		// Создаем список поддиректорий
		return $path."/".wordwrap(self::dec_hex($value), 2, "/", true);
	}

	/**
	 * Проверка на вхождение адреса в сеть
	 * @param string $network Сеть в виде 127.0.0.1/24
	 * @param string $ip Проверяемый адрес
	 * @return bool
	 */
	public static function net_match($network, $ip) {
		// Парсим сеть
		$ip_arr = explode("/", $network);
		// Если был установлен просто адрес, то - добавляем маску
		if (count($ip_arr) == 1) $ip_arr[] = "255.255.255.255";
		// Получаем начальный адрес сети в виде числа
		$network_long = ip2long($ip_arr[0]);
		// Преобразуем адрес в число
		$ip_long = ip2long($ip);
		// Преобразуем маску сети в число
		$x = ip2long($ip_arr[1]);
		// Если маска сети число оставляем как есть
		// иначе - получаем путем сдвига бит влево
		$mask = (long2ip($x) == $ip_arr[1]) ? $x : 0xffffffff << (32 - $ip_arr[1]);
		// Проверяем на вхождение в маску сети
		return ($ip_long & $mask) == ($network_long & $mask);
	}

    /**
     * Base32 encode
     * @param string $data
     * @param string $base
     * @return string
     */
    public static function base32_encode($data, $base = "abcdefghijklmnopqrstuvwxyz234567") {
        $data_size = strlen($data);
        $res = ""; $remainder = $remainder_size = 0;
        for ($i = 0; $i < $data_size; $i++) {
            $b = ord($data[$i]);
            $remainder = ($remainder << 8) | $b;
            $remainder_size += 8;
            while ($remainder_size > 4) {
                $remainder_size -= 5;
                $c = $remainder & (31 << $remainder_size);
                $c >>= $remainder_size;
                $res .= $base[$c];
            }
        }
        if ($remainder_size > 0) {
            $remainder <<= (5 - $remainder_size);
            $c = $remainder & 31;
            $res .= $base[$c];
        }
        return $res;
    }

    /**
     * Base32 decode
     * @param string $data
     * @param string $base
     * @return string
     */
    public static function base32_decode($data, $base = "abcdefghijklmnopqrstuvwxyz234567") {
        $data_size = strlen($data);
        $res = ""; $buf = $buf_size = 0;
        $char_map = array_flip(str_split($base));
        for ($i = 0; $i < $data_size; $i++) {
            $c = $data[$i];
            if (!isset($char_map[$c])) {
                if ($c == " " || $c == "\r" || $c == "\n" || $c == "\t") continue;
            }
            $b = $char_map[$c];
            $buf = ($buf << 5) | $b;
            $buf_size += 5;
            if ($buf_size > 7) {
                $buf_size -= 8;
                $b = ($buf & (0xff << $buf_size)) >> $buf_size;
                $res .= chr($b);
            }
        }
        return $res;
    }

}
