<?php

// Основное пространство имен
namespace traits;

/**
 * Класс с дополнительными утилитами
 */
class tools {

    /**
     * Ищет путь к директории с заданным именем из исходного
     * @param $path
     * @param $name
     * @return mixed
     */
    public static function dirname($path, $name) {
        // Текущая директория
        $dir = dirname($path);
        // Если дошли до главной возвращаем просто главную
        if ($dir == '.') return '/';
        // Если дошли до заданной то возвращаем путь к ней
        else if (basename($dir) == $name) return $dir;
        // Иначе делеаем рекрсивный поиск
        else return self::dirname($dir, $name);
    }

    /**
     * Подключение файла
     * В случае отсутствия файла выполнение будет прервано
     * @param string $path Путь к подключаемому файлу
     */
    public static function loading($path) {
        // Если найден то просто подключение
        if (file_exists($path)) require_once $path;
        // Иначе выводим сообщние и прерываем выполнение
        else {
            // Трассировка
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
            // Если передан массив трассировки
            if (isset($backtrace[1])) {
                // Извлекаем последний путь из трассировки
                $trace = $backtrace[1];
                // Добавляем путь трассировки
                $add = isset($trace['file']) ? 'Вызов из файла: '. $trace['file'].' линия: '.$trace['line']."\r\n" : '';
            // Иначе ничего не добавляем
            } else $add = '';
            // Вывод сообщения
            show::alert($add.'Не найден файл: '.$path, true);
        }
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
        $find = '';
        // Поиск регулярного выражения
        if ($result = preg_match($reg, $data, $match)) $find = trim($match[$pos]);
        // Если не найдено и установлен флаг остановки - прерываем выполнение
        else if ($stop) show::alert('Не найдено регулярное выражение: '.$reg, $stop, $data);
        // Вывод результата
        return $result;
    }
}