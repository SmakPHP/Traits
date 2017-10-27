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
                $add = (isset($trace['file'])) ? 'Вызов из файла: '. $trace['file'].' линия: '.$trace['line']."\r\n" : '';
            // Иначе ничего не добавляем
            } else $add = '';
            // Вывод сообщения
            rider::alert($add.'Не найден файл: '.$path, true);
        }
    }
}