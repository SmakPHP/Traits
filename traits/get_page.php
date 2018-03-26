<?php

// Основное пространство имен
namespace traits;

/**
 * Класс для чтения страницы
 *
 * Дополнительно:
 * http://php.net/manual/ru/function.curl-setopt.php
 *
 * Пример использования:

	// Подключение библиотеки
	require_once "traits/register.php";

	// Инициализация класса вывода
	$show = new traits\show(__DIR__);

	// Инициализация класса загрузки страниц
	$pager = new traits\get_page();
	// Запрашиваем страницу
	$data = $pager->get("http://php.net");

 */
class get_page {

	/**
	 * Дополнительные заголовки
	 * @var array
	 */
	public $extra = array();

	/**
	 * Полученные заголовки
	 * @var string
	 */
	public $header = "";

	/**
	 * Полученные данные
	 * @var string
	 */
	public $data = "";

	/**
	 * Отладка
	 * @var string
	 */
	private $debug = "";

	/**
	 * Прокси
	 * @var array
	 */
	private $proxy = array("type" => "sock5",
												 "host" => "127.0.0.1:8888");

	/**
	 * Поддерживаемые типы прокси
	 * @var array
	 */
	private $types = array("sock5", "http");

	/**
	 * Фильтр прокси
	 * @var int
	 */
	private $filter = "#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\:\d{2,5}$#";

	/**
	 * Максимальное время ожидания данных
	 * @var int
	 */
	private $timeout = 30;

	/**
	 * Максимальное количество редиректов
	 * @var int
	 */
	private $redirect = 6;

	/**
	 * Разрешить редирект на любой хост
	 * @var bool
	 */
	private $all = false;

	/**
	 * Подсчет редиректов
	 * @var int
	 */
	private $counter = 0;

	/**
	 * Максимальный размер данных
	 * @var int
	 */
	private $length = 0;

	/**
	 * Браузер
	 * @var string
	 */
	private $agent = "";

	/**
	 * Ссылающаюся страница
	 * @var string
	 */
	private $referer = "";

	/**
	 * Директория вывода
	 * @var string
	 */
	private static $path = null;

	/**
	 * Конструктор класса
	 * @param string $path Директория вывода
	 * @param array $proxy Прокси
	 * @param int $redirect Максимальное количество редиректов
	 * @param bool $all Флаг разрешающий переадресацию на любой хост
	 * @param int $timeout Максимальное время ожидания данных
	 * @param bool $debug Флаг отладки
	 * @throws \Exception
	 */
	public function __construct($path = "", $proxy = array(), $redirect = 6,
															$all = false, $timeout = 30, $debug = false) {
		// Валидация прокси
		if (isset($proxy["type"]) && in_array($proxy["type"], $this->types) &&
				isset($proxy["host"]) && preg_match($this->filter, $proxy["host"])) $this->proxy = $proxy;
		// Иначе просто сбрасываем
		else $this->proxy = array();
		// Установка максимальное количества редиректов
		$this->redirect = intval($redirect);
		// Установка максимального времени ожидания
		$this->timeout = intval($timeout);
		// Установка режима переадресации
		$this->all = ($all) ? true : false;
		// Установка браузера
		$this->agent = "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) ".
									 "Chrome/62.0.3202.62 Safari/537.36";
		// Установка директории вывода
		self::$path = is_dir($path) ? realpath($path) : null;
		// Если установлен флаг отладки
		if ($debug) {
			// Если установлена директория для вывода
			if (!is_null(self::$path)) {
				// Устанавливаем путь у файлу отладки заголовков
				$this->debug = self::$path."/debug.header.log";
			// Иначе выбрасываеем исключение
			} else throw new \Exception("Не уcтановлена директория для вывода");
		}
	}

	/**
	 * Чтение страницы
	 * @param string $link Запрашиваемая страница
	 * @param array $post Отправляемые данные
	 * @param string $referer Рефер
	 * @param string $cookie Куки
	 * @param int $cleaning Флаг очистки кук
	 * @param int $start Получить данные с позиции
	 * @param int $length Длинной
	 * @return bool|string
	 * @throws \Exception
	 */
	public function get($link, $post = array(), $referer = "", $cookie = "auto",
											$cleaning = 8, $start = -1, $length = 2000000) {
		// Инициализация
		$this->header = $this->data = "";
		// Устанавливаем максимальный размер данных
		$this->length = intval($length);
		// Подсчитываем количество редиректов
		$this->counter++;
		// Парсим исходную ссылку
		$parse = parse_url($link);
		// Инициализация библиотеки
		$curl = curl_init();
		// Установка запращиваемой страницы
		curl_setopt($curl, CURLOPT_URL, $link);
		// Установка браузера
		curl_setopt($curl, CURLOPT_USERAGENT, $this->agent);
		// Если не установлена ссылающаяся страница
		if ($referer == "") {
			// Если была установлена до этого берем её
			if (strlen($this->referer)) $referer = $this->referer;
			// Иначе генерируем из текущей ссылки
			else $referer = $parse["scheme"]."://".$parse["host"]."/";
		}
		// Устанавливаем ссылающуюся страницу
		$this->referer = $link;
		// Устанавливаем ссылающуюся страницу
		curl_setopt($curl, CURLOPT_REFERER, $referer);
		// Если установлены дополнительные заголовки
		if (count($this->extra)) $headers = $this->extra; else $headers = array();
		// Если установлено начало получаемой части
		if ($start >= 0) $headers[] = "Range: bytes=".$start."-";
		// Устанавливаем заголовки браузера
		if (count($headers)) curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		// Для чтения страниц по защищенному протоколу
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		// Возвращаем результат
		curl_setopt($curl, CURLOPT_HEADER, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		// Установка максимального времени получения данных
		curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
		// Установка времени соединения
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->timeout);
		// Если использовать прокси
		if (isset($this->proxy["type"])) {
			// Выбор типа прокси
			if ($this->proxy["type"] == "sock5") {
				// Устанавливаем тип прокси
				curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
			}
			// Установка хоста прокси
			curl_setopt($curl, CURLOPT_PROXY, $this->proxy["host"]);
		}
		// Ограничиваем размер загрузки
		curl_setopt($curl, CURLOPT_WRITEFUNCTION, array($this, "process"));
		// Если отправка данных
		if (count($post)) {
			// Устанавливаем отправляемые данные
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
		}
		// Если установлены куки
		if (strlen($cookie)) {
			// Если автоматическая генерация имени
			$file = ($cookie == "auto") ? "cookie.".tools::domain($parse["host"]).".txt" : $cookie;
			// Если установлена директория для вывода
			if (!is_null(self::$path)) {
				// Очистка кук при необходимости
				file_put_contents(self::$path."/".$file, "", $cleaning);
				// Подключаем поддержку кук
				curl_setopt($curl, CURLOPT_COOKIEFILE, self::$path."/".$file);
				curl_setopt($curl, CURLOPT_COOKIEJAR, self::$path."/".$file);
			// Вывод исключения если не установлена директория для вывода
			} else throw new \Exception("Не уcтановлена директория для вывода");
		}
		// Если отладка
		if (strlen($this->debug)) {
			// Устанавливаем файл для записи заголовов
			$sniff = fopen($this->debug, "a");
			fwrite($sniff, date("Y-m-d H:i:s")." - Header:\r\n");
			curl_setopt($curl, CURLOPT_STDERR, $sniff);
			curl_setopt($curl, CURLOPT_VERBOSE, 1);
		// Иначе файл отладки не открыт
		} else $sniff = false;
		// Выполняем чтение страницы с установленными параметрами
		curl_exec($curl);
		// Проверяем наличие ошибки
		if (curl_errno($curl) && strlen($this->debug)) {
			// Выводим ошибку
			show::alert("Ошибка: ".curl_error($curl).", при чтении страницы: ".$link);
		}
		// Закрываем соединение
		curl_close($curl);
		// Если файл отладки открыт
		if (is_resource($sniff)) fclose($sniff);
		// Пока в полученных данных находим служебный заголовок
		while (stripos($this->data, "HTTP/1") === 0) {
			// Разбиваем на заголовок и данные
			list($header, $this->data) = explode("\r\n\r\n", $this->data, 2);
			// Собираем заголовок
			$this->header = ltrim($this->header."\r\n".$header);
		}
		// Если данные упакованы
		if (tools::find_reg("|Content-Encoding: (\\w+)|i", $this->header, $find) && ($start == -1)) {
			// Распаковка данных из gz формата
			if ($find == "gzip") $this->data = gzinflate(substr($this->data, 10, -8));
			// Распаковка данных из deflate формата
			else if ($find == "deflate") $this->data = gzinflate($this->data);
		}
		// Если установленна переадресация
		if (tools::find_reg("|^Location: (.+)|im", $this->header, $location) && ($this->counter <= $this->redirect)) {
			// Если указание полного пути
			if ((strpos($location, "http://") === 0) || (strpos($location, "https://") === 0)) {
				// Получаем главный хост
				$host = tools::domain(parse_url($location, PHP_URL_HOST));
				// Разрешаем переадресацию только если хосты схожы или разрешена переадресация на все
				if (((strpos($host, $parse["host"]) !== false) ||
					 (strpos($parse["host"], $host) !== false)) || $this->all) {
					// Вывод в лог если отладка
					if (strlen($this->debug)) show::alert("Переадресация (1) - ".$location.", ".$this->counter);
					// Рекурсивное чтение страницы
					$this->data = $this->get($location, array(), "", $cookie, 8, $start, $length);
				// Иначе - выводим ошибку
				} else show::alert("В переадресации отказано - ".$location.", хост: ".$parse["host"]);
			// Если начинается на слеш
			} else if ($location[0] == "/") {
				// Добавляем протокол и хост
				$location = $parse["scheme"]."://".$parse["host"].$location;
				// Вывод в лог если отладка
				if (strlen($this->debug)) show::alert("Переадресация (2) - ".$location.", ".$this->counter);
				// Рекурсивное чтение страницы
				$this->data = $this->get($location, array(), "", $cookie, 8, $start, $length);
			// Если начинается на букву
			} else if (preg_match("#^\w+#", $location)) {
				// Добавляем протокол и хост
				$location = $parse["scheme"]."://".$parse["host"]."/".$location;
				// Вывод в лог если отладка
				if (strlen($this->debug)) show::alert("Переадресация (3) - ".$location.", ".$this->counter);
				// Рекурсивное чтение страницы
				$this->data = $this->get($location, array(), "", $cookie, 8, $start, $length);
			// Иначе - выводим ошибку
			} else show::alert("Не возможно переадресовать - ".$location);
		}
		// Сбрасываем счетчик редиректов
		$this->counter = 0;
		// Выводим результат
		return substr($this->data, 0, $length);
	}

	/**
	 * Обработчик получения данных
	 * @param $handle
	 * @param $data
	 * @return int
	 */
	private function process($handle, $data) {
		// Получаем данные
		$this->data .= $data;
		// Если достигли заданного размера - прерываем
		if (strlen($this->data) > ($this->length + 1000)) return 0; else return strlen($data);
	}

	/**
	 * Функция раскрытия коротких ссылок
	 * @param string $correct Путь для раскрытия
	 * @param string $link Ссылка с хостом
	 * @param string $anchor Анкор
	 * @param string $info Дополнительная информация
	 * @return mixed|string
	 */
	public static function filter($correct, $link, &$anchor, $info = "") {
		// Отключаем скрипты
		if (strpos($correct, "javascript:") === 0) { $anchor = "#js"; return ""; }
		// Ищем анкор
		if (strpos($correct, "#") !== false) {
			// Извлекаем анкор
			list($correct, $anchor) = explode("#", $correct, 2); $anchor = "#".$anchor;
			// Иначе просто корректировка
		} else $anchor = "";
		// Если нет ссылки
		if ($correct == "") return "";
		// Преобразуем все HTML-сущности в соответствующие символы
		$correct = str_replace(" ", "%20", html_entity_decode(trim($correct)));
		// Парсим ссылку открытой страницы
		$parse = parse_url($link);
		// Просто хостовая ссылка
		$host_link = $parse["scheme"]."://".$parse["host"];
		// Убираем режим короткой ссылки
		if (strpos($correct, "//") === 0) $correct = "http:".$correct;
		// Если нормальная ссылка - пропускаем
		else if ((strpos($correct, "http://") === 0) || (strpos($correct, "https://") === 0)) {}
		// Из сокращенного пути делаем полный
		else if ($correct[0] == "/") $correct = $host_link.$correct;
		else if (strpos($correct, "./") === 0) $correct = $host_link.substr($correct, 1);
		else if (preg_match("#^[\w\?]+#", $correct)) $correct = $host_link."/".$correct;
		else if (strpos($correct, "../") === 0) {
			// Парсим путь к документу источнику
			if (isset($parse["path"])) {
				// Разбиваем путь на папки
				$path = explode("/", $parse["path"]);
				// Убираем первый элемент и если остались субпапки
				array_shift($path); $count = count($path); if ($count) {
					// Если найдена точка, дополнительно поднимаем
					if (strpos($path[$count-1], ".") !== false) array_pop($path);
				}
			// В случае отсутствия - инициализация
			} else $path = array();
			// Выполняем пока есть сокращенный путь
			while (strpos($correct, "../") === 0) {
				// Убираем сокращенный путь
				if (strlen($correct) > 3) $correct = substr($correct, 3); else $correct = "";
				// Выталкиваем папку
				array_pop($path);
			}
			// В конец массива субпапок добавляем оставшийся путь к файлу
			if (strlen($correct)) array_push($path, $correct);
			// В начало массива - хостовую ссылку и получаем результат
			array_unshift($path, $host_link); $correct = implode("/", $path);
		// Иначе - ошибка
		} else {
			// Добавляем в лог файл для анализа
			show::alert("Не корретная ссылка: ".$correct.", ".$info, false, "", "nolink.txt");
			// Меняем ссылку на главную страницу
			$correct = $host_link."/";
		}
		// По умолчанию ок
		return $correct;
	}
	
}
