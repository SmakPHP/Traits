<?php

// Основное пространство имен
namespace restrict;

/**
 * Автоматическое подключение составных типов
 */
class dynamically {

	/**
	 * Подключение составных элементов
	 */
	public function __construct() {
		// Получение составных элементов
		$args = func_get_args();
		// Если есть переданные элементы
		if (count($args)) {
			// Устанавливаем переданные аргументы
			foreach ($args as $arg) $this->add('auto', $arg, true);
		}
	}

	/**
	 * Добавить параметр
	 * @param string $name Имя объекта
	 * @param mixed $arg Устанавливаемый объект
	 * @param bool $check Проверка поддержки класса
	 * @throws \Exception
	 */
	public function add($name, $arg, $check = false) {
		// Получаем название текущего класса
		$self = str_replace('restrict\\', '', get_class($this));
		// Получаем название класса из элемента
		$class = (strlen($name) && $name != 'auto') ? $name : get_class($arg);
		$class = str_replace('restrict\\', '', $class);
		// Если класс не поддерживается
		if ($check && !class_exists('restrict\\'.$class)) {
			// Иначе выбрасываем исключение
			throw new \Exception('Класс: restrict\\'.$class.' не поддерживается!');
		}
		// Проверяем поддерживается ли составной элемент
		if (property_exists($this, $class)) {
			// Если составной элемент массив то добавляем как элемент массива
			if (is_array($this->{$class}) && isset($this->{$class}['array'])) $this->{$class}[] = $arg;
			// Иначе просто устанавливаем как элемент
			else $this->{$class} = $arg;
		// Иначе выбрасываем исключение
		} else throw new \Exception('Класс: restrict\\'.$class.' не поддерживается в '.$self.' !');
	}

	/**
	 * Удаление не используемых элементов
	 * @param null $self Текущий объект
	 */
	public function clean(&$self = null) {
		// Если первый запуск
		if (is_null($self)) $self =& $this;
		// Перебираем все элементы класса
		foreach ($self as $name => $value) {
			// Если элемент вложенный класс
			if (is_object($value)) {
				// Выполняем рекурсию
				$this->clean($value);
			// Иначе если переменная не определена то удаляем
			} else if (is_null($value)) {
				// Удаляем из данного объекта
				unset($self->{$name});
			}
		}
	}

	/**
	 * Экспорт в массив
	 * @param null $self
	 * @return array Текущий объект
	 */
	public function result($self = null) {
		// Инициализация
		$result = array();
		// Если первый запуск
		if (is_null($self)) {
			// Удаляем не используемые элементы
			$this->clean();
			// Указываем на текущий класс
			$self =& $this;
		}
		// Перебираем все элементы класса
		foreach ($self as $name => $value) {
			// Корректируем название
			$name = str_replace('_', '-', $name);
			// Пропускаем спец ключ
			if ($name == 'array') continue;
			// Если элемент вложенный класс
			if (is_object($value) || is_array($value)) {
				// Выполняем рекурсию
				$result[$name] = $this->result($value);
			// Иначе если переменная не определена то удаляем
			} else $result[$name] = $value;
		}
		// Вывод результата
		return $result;
	}

}

/**
 * Список организаций
 */
class organization extends dynamically {

	/**
	 * Идентификатор федерального сегмента
	 * @var string [0..1] xs:string
	 */
	public $fsGuid;


	/**
	 * Идентификатор регионального сегмента
	 * @var string [0..1] xs:string
	 */
	public $regGuid;

	/**
	 * Список сведений о смене статусов
	 * @var string [1..n] составной
	 */
	public $statusItem = array('array' => true);

}

/**
 * Список статусов
 */
class statusItem extends dynamically {

	/**
	 * Статус
	 * @var string [1] xs:unsignedInt
	 */
	public $status;


	/**
	 * Дата изменения статуса
	 * @var string [1] xs:date
	 */
	public $updateDate;

}
