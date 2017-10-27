<?php

// Основное пространство имен
namespace traits;

/**
 * Класс вывода
 */
class rider {

    /**
     * Безопасный вывод сообщения
     * @param $message
     * @param bool $stop
     */
    public static function alert($message, $stop = false) {
        // Выводим сообщение
        echo '<pre>'.htmlspecialchars($message).'</pre>';
        // Прерываем выполнение если установлен флаг
        if ($stop) die();
    }

}