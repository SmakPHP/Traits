<?php

// Основное пространство имен
namespace traits;

/**
 * Расширение документа
 */
class xml_dom extends \DOMDocument {

    /**
     * Конструктор класса документа
     * @param string $xml Документ
     */
    public function __construct($xml){
        // Указание не убирать лишние пробелы и отступы
        $this->preserveWhiteSpace = false;
        // Форматирует вывод, добавляя отступы и дополнительные пробелы
        $this->formatOutput = false;
        // Загружаем документ
        $this->loadXML($xml);
    }

    /**
     * Чтение значения документа
     * @param string $name Название элемента
     * @param string $uri Простраство имен
     * @param int $num
     * @return \DOMElement
     */
    public function get($name, $uri = '', $num = 0) {
        // Если установлено простраство имен ищем с его указанием
        if (strlen($uri)) return $this->getElementsByTagNameNS($uri, $name)->item($num);
        // Иначе ищем без указания пространства имен
        else return $this->getElementsByTagName($name)->item($num);
    }

    /**
     * Установка значения документа
     * @param string $name Название элемента
     * @param string $value Устанавливаемое значение
     * @param string $uri Простраство имен
     * @param int $num
     */
    public function set($name, $value, $uri = '', $num = 0) {
        // Если установлено пространство имен устанавливаем для заданного элемента с его учетом
        if (strlen($uri)) $this->getElementsByTagNameNS($uri, $name)->item($num)->nodeValue = $value;
        // Иначе устанавливаем без указания пространства имен
        else $this->getElementsByTagName($name)->item($num)->nodeValue = $value;
    }

}