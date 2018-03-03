<?php

// Основное пространство имен
namespace traits;

/**
 * Класс преобразования массива в xml документ
 *
 * За основу взят скрипт
 * https://github.com/jmarceli/array2xml
 *
 * Доработки:
 * 1. Каждый внутренний элемент не оборачивается внешним ключем, вложенность > 3
 * 2. Возможность вставки "серых" данных через флаг &
 * 3. Возможность установки пространства имен
 *
 * Пример использования:

	require_once 'traits/register.php';
	$xml = traits\array_xml::extent('inf')->build(array('key' => 'value'));

 */
class array_xml {

	/**
	 * Пространство имен
	 * @var string
	 */
	private $prefix = '';

	/**
	 * Конструктор класса
	 * @param string $prefix Пространство имен
	 */
	public function __construct($prefix) {
		// Установка пространства имен
		$this->prefix = (strlen($prefix)) ? $prefix.':' : '';
	}

	/**
	 * Возвращаем экземляр текущего класса с заданным пространством имен
	 * @param $prefix
	 * @return array_xml
	 */
	public static function extent($prefix = '') {
		// Возвращаем текущий экземляр класса
		return new self($prefix);
	}

	/**
	 * Сборка документа
	 * @param array $data Массив для преобразования в документ
	 * @param string $version
	 * @param string $encoding
	 * @return string
	 */
	public function build($data, $version = '', $encoding = '') {
		// Инициализация
		$xml = new \XmlWriter();
		$xml->openMemory();
		// Если установлена версия и кодировка документа
		if (strlen($version) && strlen($encoding)) {
			// Устанавливаем версию и кодировку документа
			$xml->startDocument($version, $encoding);
		}
		// Сборка документа
		$this->writeElement($xml, $data);
		// Вывод результата
		return $xml->outputMemory(true);
	}

	/**
	 * Запись атрибута элемента
	 * @param \XMLWriter $xml
	 * @param array $data Массив с атрибутами
	 * @return array|mixed
	 */
	protected function writeAttr(\XMLWriter $xml, $data) {
		// Если передан массив
		if (is_array($data)) {
			// Обрабатываем только если не пустой массив
			if (!empty($data)) {
				// Пространство имен
				$prefix = $this->prefix;
				// Инициализация массива без атрибута
				$nonAttributes = array();
				// Перебираем массив с атрибутами
				foreach ($data as $key => $val) {
					if ($key[0] == '@') {
						$xml->writeAttribute($prefix.substr($key, 1), $val);
					} else if ($key[0] == '%') {
						if (is_array($val)) $nonAttributes = $val;
						else $xml->text($val);
					} else if ($key[0] == '&') {
						if (is_array($val)) $nonAttributes = $val;
						else {
							$xml->startElement($prefix.substr($key, 1));
							$xml->writeRaw("$val");
							$xml->endElement();
						}
					} elseif ($key[0] == '#') {
						if (is_array($val)) $nonAttributes = $val;
						else {
							$xml->startElement($prefix.substr($key, 1));
							$xml->writeCData($val);
							$xml->endElement();
						}
					} else if ($key[0] == "!") {
						if (is_array($val)) $nonAttributes = $val;
						else $xml->writeCData($val);
					} else $nonAttributes[$key] = $val;
				}
				return $nonAttributes;
			// Иначе сбрасываем данные
			} else return '';
		// Иначе выводим как есть
		} else return $data;
	}

	/**
	 * Запись элемента
	 * @param \XMLWriter $xml
	 * @param array $data Ассоциативный массив
	 */
	protected function writeElement(\XMLWriter $xml, $data) {
		// Если передан массив
		if (is_array($data)) {
			// Пространство имен
			$prefix = $this->prefix;
			// Перебираем массив с элементами
			foreach ($data as $key => $value) {
				// Если элемент является массивом и не ассоциативный
				if (is_array($value) && !$this->isAssoc($value)) {
					// Оборачиваем в текущий элемент
					$xml->startElement($prefix.$key);
					// Перебираем вложенные элементы
					foreach ($value as $itemValue) {
						// Получаем атрибуты элемента
						$itemValue = $this->writeAttr($xml, $itemValue);
						// Если элемент массив то выполняем рекурсивный вызов
						if (is_array($itemValue)) $this->writeElement($xml, $itemValue);
						// Иначе добавляем как строковое представление
						else $xml->writeElement($prefix.$key, "$itemValue");
					}
					// Закрываем текущий элемент
					$xml->endElement();
				// Если элемент является массивом
				} else if (is_array($value)) {
					$xml->startElement($prefix.$key);
					$value = $this->writeAttr($xml, $value);
					$this->writeElement($xml, $value);
					$xml->endElement();
				// Если стоит модификатор
				} else if (in_array($key[0], array('&', '!', '@', '#', '%'))) {
					$this->writeAttr($xml, array($key => $value));
				// Иначе просто элемент
				} else {
					$value = $this->writeAttr($xml, $value);
					$xml->writeElement($prefix.$key, "$value");
				}
			}
		}
	}

	/**
	 * Проверка массива на ассоциативность
	 * @param array $array Массив для проверки
	 * @return bool
	 */
	protected function isAssoc($array) {
			return (bool)count(array_filter(array_keys($array), 'is_string'));
	}
}
