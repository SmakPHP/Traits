<?php

// Основное пространство имен
namespace traits;

/**
 * Класс работы со структуированными записями
 */
class record {

	/**
	 * Сколько всего записей
	 * @var float|int
	 */
	public $total = 0;

	/**
	 * Текущая директория
	 * @var
	 */
	public $dir;

	/**
	 * Индекс
	 * @var string
	 */
	public $index = "";

	/**
	 * Структура
	 * Нельзя использовать: start, true, false
	 * @var array
	 */
	public $type = array(
		"id"      => "index",     // Индекс должен быть первым - 10 байт
		"get"     => "byte",      // Статическая переменная с размером в 1 байт, integer - 10 байт
		"link"    => "string",    // Строка переменной длины, в которой можно делать поиск
		"type"    => "pack",      // Строка переменной длины, без дубликатов
		"content" => "data"       // Данные к которым применяется компрессия
	);

	/**
	 * Размерность
	 * @var int
	 */
	public $size = 0;

	/**
	 * Размерность записи
	 * @var array
	 */
	public $starts = array();

	/**
	 * Кеширование строк и размерности данных
	 * @var array
	 */
	public $cache = array();

	/**
	 * Кеширование сохранения
	 * @var array
	 */
	public $save = array();

	// Конструктор класса
	public function __construct($dir) {
		// Установка директории
		$this->dir = $dir;
		// Если индекс файл существует - открываем
		if (file_exists($this->dir."/index.dat")) {
			// Загрузка индекс файла
			$this->index = files::read_file($this->dir."/index.dat");
		}
		// Перебираем значения структуры
		foreach ($this->type as $key => $type) {
			// Начало в индекс строке
			$this->starts[$key] = $this->size;
			// Суммируем
			if ($type == "index") $this->size += 10;
			else if ($type == "byte") $this->size += 1;
			else if ($type == "integer") $this->size += 10;
			else if ($type == "string") { $this->size += 18; $this->cache[$key] = ""; }
			else if ($type == "pack") { $this->size += 18; $this->cache[$key] = ""; }
			else if ($type == "data") { $this->size += 20; $this->cache[$key] = 0; }
			// Плюс два символа на завершение индекс строки
		} $this->size += 2;
		// Получаем количество записей
		$this->total = strlen($this->index) / $this->size;
	}

	/**
	 * Добавить запись
	 * @param array $record Добавляем запись
	 * @return float|int
	 */
	public function add_record($record) {
		// Инициализация и очистка кеша
		$result = 0; $write = ""; clearstatcache(); $find = array();
		// Если передан массив
		if (is_array($record)) {
			// Перебираем значения структуры
			foreach ($this->type as $key => $type) {
				// Прописан ли в структуре
				if (isset($record[$key])) {
					// Получаем значение
					$value = $record[$key];
					// Если индекс
					if ($type == "index") {
						// Плюс один к количеству записей
						$this->total++; $write .= sprintf("%010d", $this->total); $result = $this->total;
						// Если символ/байт
					} else if ($type == "byte") {
						// Если значение больше максимального
						if (strlen($value) > 1) show::alert("Байт не более 1 символа: ".$value, true);
						// Добавляем данные
						$write .= $value;
					// Если число
					} else if ($type == "integer") {
						// Если значение больше максимального
						if (strlen($value) > 10) show::alert("Число не более 10 символов: ".$value, true);
						// Добавляем данные
						$write .= sprintf("%010d", $value);
					// Если строка
					} else if (($type == "string") || ($type == "pack")) {
						// Если есть данные
						if (strlen($value)) {
							// Не допускаем появления специального тега
							$value = str_replace("<r>", "<~r~>", $value);
							// Если сжатая строка - ищем дубликаты
							if ($type == "pack") $find = $this->find_record($key, $value);
							// Если найден дубликат
							if (is_array($find) && count($find)) {
								// Устанавливаем позиции данных
								$write .= $find["s".$key];
								$write .= $find["l".$key];
							} else {
								// Добавляем индекс
								$value .= "<r>".sprintf("%010d", $result)."<r>";
								// Если строка пустая и есть файл
								if (($this->cache[$key] == "") && file_exists($this->dir."/".$key.".dat")) {
									// Загружаем в кеш
									$this->cache[$key] = files::read_file($this->dir."/".$key.".dat");
								}
								// Длина строки в кеше
								$size = strlen($this->cache[$key]);
								// Если есть строка
								if ($size) {
									// Добавляем значения
									$write .= sprintf("%010d", $size);
									$write .= sprintf("%08d", strlen($value));
								} else {
									// Добавляем индекс (Для полного поиска строки, между индексами)
									$len = strlen($value);  $value = "~<r>".$value;
									// Добавляем значения
									$write .= "0000000004";	$write .= sprintf("%08d", $len);
								}
								// Измение кеш данных и пометка на сохранение
								$this->cache[$key] .= $value;
								$this->save[$key] = true;
							}
							// Иначе пустой блок
						} else $write .= "000000000000000000";
						// Если сжатые данные
					} else if ($type == "data") {
						// Если есть данные
						if (strlen($value)) {
							// Сжимаем данные
							$value = gzencode($value, 9); $len = strlen($value);
							// Если не установлен размер данных и файл найден
							if (($this->cache[$key] == 0) && file_exists($this->dir."/".$key.".dat")) {
								// Добавляем размер файла
								$this->cache[$key] = filesize($this->dir."/".$key.".dat");
							}
							// Добавляем значения
							$write .= sprintf("%012d", $this->cache[$key]); $write .= sprintf("%08d", $len);
							// Сохранение в файл и изменение размерности данных, данных много сохраняем в любом случае
							files::add_file($this->dir."/".$key.".dat", $value, true, "");
							// Добавляем в кеш
							$this->cache[$key] += $len;
						// Иначе пустой блок
						} else $write .= "00000000000000000000";
					}
				} else show::alert("Ключа нет в записи: ".$key, true);
			}
			// Добавляем перевод строки
			$this->index .= $write."\r\n";
		}
		// Выводе результата
		return $result;
	}

	/**
	 * Чтение записи
	 * @param int $id Идентификатор записи
	 * @return array
	 */
	public function read_record($id) {
		// Инициализация и очистка кеша файлов
		$result = array(); clearstatcache();
		// Проверка на количество
		if ($this->total < $id) {
			// Вывод сообщения
			show::alert("Нет записи с таким идентифкатором: ".$id.", всего: ".$this->total, true);
		}
		// Получаем индексы
		$get = substr($this->index, ($id - 1) * $this->size, $this->size);
		// Восстанавливаем структуру
		foreach ($this->type as $key => $type) {
			// Если число или индекс
			if (($type == "index") || ($type == "integer")) {
				// Добавляем значение
				$result[$key] = (int)(substr($get, $this->starts[$key], 10));
			// Если байт/символ
			} else if ($type == "byte") {
				// Добавляем значение
				$result[$key] = $get[$this->starts[$key]];
			// Если строка или строка без дубликатов
			} else if (($type == "string") || ($type == "pack"))  {
				// Получаем позиции начала и длины
				$result["s".$key] = substr($get, $this->starts[$key], 10); $start = (int)($result["s".$key]);
				$result["l".$key] = substr($get, $this->starts[$key] +10,8); $len = (int)($result["l".$key]);
				// Если данные в кеше
				if (strlen($this->cache[$key]) > 0 ) {
					// Читаем данные из кеша и устанавливаем данные
					$result[$key] = preg_replace("#<r>\d+<r>#", "", substr($this->cache[$key], $start, $len));
				} else {
					// Если файл найден
					if ($len > 0 && file_exists($this->dir."/".$key.".dat")) {
						// Читаем данные из файла и устанавливаем данные
						$data = files::read_file($this->dir."/".$key.".dat", $start, $len);
						// Удаление лишнего текста
						$result[$key] = preg_replace("#<r>\d+<r>#", "", $data);
					// Иначе пустая строка
					} else $result[$key] = "";
				}
			// Если сжатые данные
			} else if ($type == "data") {
				// Получаем позиции начала и длины
				$result["s".$key] = substr($get, $this->starts[$key], 12); $start = (int)($result["s".$key]);
				$result["l".$key] = substr($get, $this->starts[$key] +12,8); $len = (int)($result["l".$key]);
				// Если файл с веб ссылкой найден
				if ($len > 0 && file_exists($this->dir."/".$key.".txt")) {
					// Читаем данные из файла
					$link = files::read_file($this->dir."/".$key.".txt");
					// Читаем данные из контент файла
					$data = get_page::get($link, array(), 3, 10, $start, $len);
					// Для отладки
					files::write_file(DW."/test.dat", $data);
					// Распаковываем данные
					$result[$key] = gzinflate(substr($data, 10, -8));
				// Если файл найден
				} else if ($len > 0 && file_exists($this->dir."/".$key.".dat")) {
					// Читаем данные из файла
					$data = files::read_file($this->dir."/".$key.".dat", $start, $len);
					// Распаковываем данные
					$result[$key] = gzinflate(substr($data, 10, -8));
					// Иначе пустая строка
				} else $result[$key] = "";
			}
		}
		// Вывод результата
		return $result;
	}

	/**
	 * Изменение записи под идентификатору
	 * @param int $id Идентификатор записи
	 * @param array $record Запись
	 * @return int|mixed
	 */
	public function modify_record($id, $record) {
		// Инициализация
		$result = 0; $write = ""; $find = array();
		// Ищем запись
		$select = $this->read_record($id);
		// Если передан массив
		if (is_array($record)) {
			// Перебираем значения в структуре
			foreach ($this->type as $key => $type) {
				// Прописан ли в структуре
				if (isset($record[$key])) {
					// Получаем значение
					$value = $record[$key];
					// Если индекс
					if ($type == "index") {
						// Добавляем данные
						$write .= sprintf("%010d", $value); $result = $value;
						// Если символ/байт
					} else if ($type == "byte") {
						// Если значение больше максимального
						if (strlen($value) > 1) show::alert("Байт не более 1 символа: ".$value, true);
						// Добавляем данные
						$write .= $value;
					// Если число
					} else if ($type == "integer") {
						// Если значение больше максимального
						if (strlen($value) > 10) show::alert("Число не более 12 символов: ".$value, true);
						// Добавляем данные
						$write .= sprintf("%010d", $value);
					// Если строка
					} else if (($type == "string") || ($type == "pack")) {
						// Если строка пустая и есть файл загружаем в кеш
						if (($this->cache[$key] == "") && file_exists($this->dir."/".$key.".dat")) {
							// Загружаем в кеш
							$this->cache[$key] = files::read_file($this->dir."/".$key.".dat");
						}
						// Проверка на прошлые данные
						$st = (int)($select["s".$key]); $ln = (int)($select["l".$key]); if ($ln) {
							// Если были - то затираем тире
							$end = $st + $ln - 3; for ($i = $st; $i < $end; $i++) $this->cache[$key][$i] = "-";
							// Помечаем на сохранение
							$this->save[$key] = true;
						}
						// Если есть данные
						if (strlen($value)) {
							// Не допускаем появления специального тега
							$value = str_replace("<r>", "<~r~>", $value);
							// Если сжатая строка - ищем дубликаты
							if ($type == "pack") $find = $this->find_record($key, $value);
							// Если найден дубликат
							if (is_array($find) && count($find)) {
								// Устанавливаем позиции данных
								$write .= $find["s".$key]; $write .= $find["l".$key];
							} else {
								// Добавляем индекс
								$value .= "<r>".sprintf("%010d", $result)."<r>";
								// Длина строки в кеше
								$size = strlen($this->cache[$key]);
								// Если есть размер
								if ($size) {
									// Добавляем значения
									$write .= sprintf("%010d", $size);
									$write .= sprintf("%08d", strlen($value));
								} else {
									// Добавляем индекс (Для полного поиска строки, между индексами)
									$len = strlen($value); $value = "~<r>".$value;
									// Добавляем значения
									$write .= "0000000004"; $write .= sprintf("%08d", $len);
								}
								// Измение кеш данных и пометка на сохранение
								$this->cache[$key] .= $value; $this->save[$key] = true;
							}
						// Иначе пустой блок
						} else $write .= "000000000000000000";
					// Если сжатые данные
					} else if ($type == "data") {
						// Если есть данные
						if (strlen($value)) {
							// Сжимаем данные
							$value = gzencode($value, 9); $len = strlen($value);
							// Если не установлен размер данных и файл найден
							if (($this->cache[$key] == 0) && file_exists($this->dir."/".$key.".dat")) {
								// Добавляем размер файла
								$this->cache[$key] = filesize($this->dir."/".$key.".dat");
							}
							// Добавляем значения
							$write .= sprintf("%012d", $this->cache[$key]); $write .= sprintf("%08d", $len);
							// Сохранение в файл и изменение размерности данных, данных много сохраняем в любом случае
							files::add_file($this->dir."/".$key.".dat", $value, true, "");
							// Добавляем в кеш
							$this->cache[$key] += $len;
						// Иначе пустой блок
						} else $write .= "00000000000000000000";
					}
				} else show::alert("Ключа нет в записи: ".$key, true);
			}
			// Замена индекс строки
			$this->index = substr_replace($this->index, $write, ($id - 1) * $this->size, $this->size-2);
		} else show::alert("Не передана изменяемая запись", true);
		// Выводе результата
		return $result;
	}

	/**
	 * Найти запись по текстовому столбцу
	 * @param string $key Столбец
	 * @param string $find Данные для поиска
	 * @param bool $full Искать целиком
	 * @param bool $fast Возвращать только идентификатор
	 * @param string $tag Начальный тег
	 * @return array|int
	 */
	public function find_record($key, $find, $full = true, $fast = false, $tag = "<r>") {
		// Инициализация
		$result = $pi = $id = 0;
		// Прописан ли в структуре
		if (isset($this->type[$key])) {
			// Если поиск байта
			if ($this->type[$key] == "byte") {
				// Получаем длину индекс данных
				$len = strlen($this->index); $pos = $this->starts[$key];
				// Массив с подсчетом и первой позицией
				$result = array("true" => 0, "false" => 0, "start" => 0);
				// Перебираем записи
				while ($pos < $len) {
					// Проверяем совпадает ли значение
					$pi++; if ($this->index[$pos] == $find) {
						// Если первая позиция, увеличиваем счетчик совпадений
						if ($result["start"] == 0) $result["start"] = $id = $pi; $result["true"]++;
						// Если не совпадает, увеличиваем счетчик не совпадений
					} else $result["false"]++;
					// Увеличиваем на длину индекс записи
					$pos += $this->size;
				}
				// Извлекаем запись
				if (!$fast && $id) $result = array_merge($this->read_record($id), $result);
			// Если поиск строки
			} else if (($this->type[$key] == "string") || ($this->type[$key] == "pack")) {
				// Если нет данных в кеше
				if ($this->cache[$key] == "") {
					// Очистка кеша файлов
					clearstatcache();
					// Если файл найден
					if (file_exists($this->dir."/".$key.".dat")) {
						// Загружаем в кеш
						$this->cache[$key] = files::read_file($this->dir."/".$key.".dat");
					}
				}
				// Если искать целиком
				if ($full) $find = $tag.$find."<r>";
				// Находим искомую строку
				if ($pos = strpos($this->cache[$key], $find)) {
					// Ищем индекс, если не полностью ищем завершающий элемент и прибавляем его длину
					if ($full) $pi = $pos + strlen($find); else if ($pi = strpos($this->cache[$key], "<r>", $pos)) $pi += 3;
					// Если индекс найден
					if ($pi) {
						// Извлекаем и преобразуем в число
						$id = (int)(substr($this->cache[$key], $pi, 10));
						// Извлекаем запись
						if ($fast) $result = $id; else $result = $this->read_record($id);
					}
				}
			}
		} else show::alert("Нет в структуре: ".$key, true);
		// Вывод результата
		return $result;
	}

	/**
	 * Сброс кеша в файлы
	 */
	public function flush_record() {
		// Сохраняем индекс файл
		files::write_file($this->dir."/index.dat", $this->index);
		// Перебираем остальные типы
		foreach ($this->save as $key => $type) {
			// Перезаписываем файл с данными
			files::write_file($this->dir."/".$key.".dat", $this->cache[$key]);
		}
	}

}