<?php

// Основное пространство имен
namespace traits;

/**
 * Класс подписи документа
 *
 * Дополнительно:
 * https://habrahabr.ru/post/300856/
 */
class sign_xml {

	/**
	 * Сертификат для подписи
	 * @var string
	 */
	private $cert = '';

	/**
	 * Путь к приватному ключу
	 * @var string
	 */
	private $pem = '';

	/**
	 * Путь к открытому ключу
	 * @var string
	 */
	private $pub = '';

	/**
	 * Путь к временному файлу
	 * @var string
	 */
	private $temp = '';

	/**
	 * Путь к временному файлу
	 * @var string
	 */
	private $openssl = '';

	/**
	 * Логирование
	 * @var string
	 */
	public $log = '';

	/**
	 * Конструктор класса подписи документа
	 * @param string $cert Путь к открытому сертификату
	 * @param string $pem Путь к закрытому сертификату
	 * @param string $openssl Путь к библиотеки
	 */
	public function __construct($cert, $pem, $openssl = 'openssl') {
		// Проверяем существование пути
		if (file_exists($cert)) {
			// Загружаем сертификат
			$data = file_get_contents($cert);
			// Регулярное выражение для поиска сертификата
			$reg = str_replace('-', '\-', '#-----BEGIN CERTIFICATE-----(.+?)-----END CERTIFICATE-----#s');
			// Если найден сертификат
			if (preg_match($reg, $data, $match)) {
				// Устанавливаем путь к библиотеки
				$this->openssl = $openssl;
				// Устанавливаем сертификат при этом убираем переводы строк
				$this->cert = str_replace(array("\r", "\n"), '', $match[1]);
				// Парсим путь к файлу с сертификатом
				$path = pathinfo($cert);
				// Путь к файлу с открытым ключем
				$this->pub = $path['dirname'].'/'.$path['filename'].'.pub';
				// Если не существует то пробуем создать
				if (!file_exists($this->pub)) {
					// Извлечение публичного ключа из сертификата и запись в файл
					$this->ssl('x509 -engine gost -inform pem -in '.$cert.' -pubkey -noout > '.$this->pub);
				}
				// Устанавливаем путь к временному файлу
				$this->temp = tempnam(sys_get_temp_dir(), md5(microtime().rand()));
				// Установка пути к закрытому сертификату
				$this->pem = $pem;
			}
		}
	}

	/**
	 * Создание дайджеста
	 * @param xml_dom $dom Расширенный документ
	 * @return mixed
	 */
	public function digiset(xml_dom $dom) {
		$data = $dom->get('Body', 'http://schemas.xmlsoap.org/soap/envelope/')->C14N(true);
		$value = $this->ssl('dgst -engine gost -md_gost94 -binary | base64', $data);
		return str_replace("\n", '', $value);
	}

	/**
	 * Создание сигнатуры
	 * @param xml_dom $dom Расширенный документ
	 * @return mixed
	 */
	public function signature(xml_dom $dom) {
		$data = $dom->get('SignedInfo', 'http://www.w3.org/2000/09/xmldsig#')->C14N(true);
		$value = $this->ssl('dgst -sign '.$this->pem.' -engine gost -binary | base64', $data);
		return str_replace("\n", '', $value);
	}

	/**
	 * Подписывание документа
	 * @param string $xml Документ который необходимо подписать
	 * @return string
	 */
	public function sign($xml) {
		// Инициализация
		$result = '';
		// Если установлен сертификат
		if (strlen($this->cert) && strlen($xml)) {
			// Создаем экземляр расширенного документа
			$dom = new xml_dom($xml);
			// Создание и установка дайджеста
			$dom->set('DigestValue', $this->digiset($dom), 'http://www.w3.org/2000/09/xmldsig#');
			// Создание и установка сигнатуры
			$dom->set('SignatureValue', $this->signature($dom), 'http://www.w3.org/2000/09/xmldsig#');
			// Установка сертификата
			$dom->set('BinarySecurityToken', $this->cert,
								'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd');
			// Установка результата
			$result = $dom->saveXml();
		}
		// Вывод результата
		return $result;
	}

	/**
	 * Проверка подписи документа
	 * @param string $data Данные для проверки
	 * @param string $key Ключ
	 * @return bool
	 */
	public function validate($data, $key) {
		// Устанавливаем путь к временному файлу
		$signature = tempnam(sys_get_temp_dir(), md5(microtime().rand()));
		// Декодируем сигнатуру
		$decode = base64_decode($key);
		// Запись декодированного ключа
		file_put_contents($signature, $decode);
		// Логирование
		$this->log .= "Файл: ".$signature." данные:\r\n".$decode."\r\n";
		// Выполняем команду проверки ключа
		$verify = $this->ssl('dgst -engine gost -binary -verify '.$this->pub.' -signature '.$signature, $data);
		// Проверка валидности подписи данных
		return (strpos($verify, 'OK') > 0);
	}

	/**
	 * Выполнение команды
	 * @param string $command Команда для выполнения
	 * @param string $data Данные которые нужно обработать
	 * @return string
	 */
	private function ssl($command, $data = '') {
		// Инициализация
		$shell = $this->openssl.' '.$command;
		// Если есть данные
		if (strlen($data)) {
			// Запись во временный файл
			file_put_contents($this->temp, $data);
			// Подключаем файл с данными
			$shell = 'cat '.$this->temp.' | '.$shell;
			// Логирование
			$this->log .= "Файл: ".$this->temp." данные:\r\n".$data."\r\nКоманда: ".$shell."\r\n";
		}
		// Выполнение команды
		$result = trim(shell_exec($shell));
		// Логирование
		$this->log .= "Результат:\r\n".$result."\r\n";
		// Вывод результата
		return $result;
	}

}
