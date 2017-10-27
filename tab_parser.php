<?php

// Основное пространство имен
namespace traits;

/**
 * Преобразование данных разделенных знаком табуляции в класс
 *
 * Пример использования:

$text = <<<'DATA'
groupMembership
originID	[1]	xs:string	Уникальный идентификатор связи в рамках системы-поставщика
Дополнительное описание
personID	[1]	xs:string	Уникальный идентификатор учащегося в рамках системы-поставщика
order
orderNumber	[0..1]	xs:string	Номер приказа
orderDate	[0..1]	xs:date	Дата приказа
orderType	[0..1]	xs:unsignedLong	Тип приказа	Из справочника «Типы и виды документов», номера 3ХХ
DATA;

$parse = new tab_parser($text);
$class = $parse->result;

 */
class tab_parser {

    /**
     * Название переменной
     * @var mixed|string
     */
    private $name = '';


    /**
     * Тип переменной
     * @var string
     */
    private $type = '';

    /**
     * Описание переменной
     * @var string
     */
    private $value = '';

    /**
     * Массив дополнительным описанием
     * @var array
     */
    private $description = array();

    /**
     * Флаг установки переменной
     * @var bool
     */
    private $var = false;

    /**
     * Флаг установки класса
     * @var bool
     */
    private $class = false;

    /**
     * Результат разбора
     * @var string
     */
    public $result = '';

    /**
     * Парсинг данных
     * @param $text
     */
    public function __construct($text) {
        // Выдергивание строк заданного формата
        if (preg_match_all('#^.+$#m', $text, $match)) {
            // Переборка все строк
            foreach ($match[0] as $select) {
                // Если начало нового класса
                if (preg_match('#^\w+\s+$#', $select)) {
                    // Оставляем только название
                    $select = trim($select);
                    // Если установлен флаг класса
                    if ($this->class) {
                        // Вывод переменной
                        $this->add();
                        // Завершаем класс
                        $this->result .= "
}
";
                    }
                    // Начинаем новый класс
                    $this->result .= "
class {$select} extends dynamically {
";
                } else {
                    // Если начинается со знака табуляции
                    if (preg_match('#^\s+[a-z]+#i', $select)) {
                        // Вывод переменной
                        $this->add();
                        // Разбивка по знаку табуляции
                        @list($name, $count, $type, $value, $additional) = explode("\t", trim($select), 5);
                        // Строковое представление количества
                        $this->type = $count.' '.$type;
                        // Меняем дифис на нижнее подчеркивание
                        $this->name = str_replace('-', '_', $name);
                        // Если множественное то инициализируем массив
                        if (strpos($count, '..n')) $this->name .= ' = array(\'array\' => true)';
                        // Начинаем описание
                        $this->value = (is_null($value)) ? '' : $value;
                        $this->description = (is_null($additional)) ? array() : array($additional);
                    // Иначе суммируем описание
                    } else {
                        // Удаляем пробелы
                        $select = trim($select);
                        // Добавляем если есть данные
                        if (strlen($select)) $this->description[] = $select;
                    }
                    // Переключаем флаг переменной
                    $this->var = true;
                }
                // Переключаем флаг класса
                $this->class = true;
            }
            // Если установлен флаг класса
            if ($this->class) {
                // Завершаем класс
                $this->result .= "
}
";
            }
        }
    }

    /**
     * Вывод переменной
     */
    private function add() {
        // Если установлен флаг переменной
        if ($this->var) {
            // Инициализация описания
            $additional = "";
            // Если есть описания
            if (count($this->description)) {
                // Перебираем описания
                foreach ($this->description as $line) {
                    // Формирование параметра
                    $additional .= "
     * {$line}";
                }
                // Удаляем последний перевод строки
                $additional = rtrim($additional);
            }
            // Формирование параметра
            $this->result .= "
    /**
     * {$this->value}{$additional}
     * @var string {$this->type}
     */
    public \${$this->name};\r\n\r\n";
            // Сброс вывода
            $this->var = false;
            $this->description = array();
        }
    }

}