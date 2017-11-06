<?php

/**
 * Функция автоматического подключения классов библиотеки
 * @param $class
 */
function register($class) {
    // Подключаем только свои библиотеки
    if (strpos($class, 'traits') === 0) {
        // Путь к подключаемому классу
        $path = dirname(__DIR__).'/'.str_replace('\\', '/', $class).'.php';
        // Если файл существует то загружаем
        if (file_exists($path)) require_once $path;
    }
}

// Регистрируем обработчик автоматического подключения классов
spl_autoload_register('register');

// Директория для вывода
define('data_dir', dirname(__DIR__).'/data/');
// Проверяем наличие директории для вывода
if (!file_exists(data_dir)) {
    // Пробуем создать директорию
    if (mkdir(data_dir, true)) {
        // Устанавливаем полный доступ
        chmod(data_dir, 0777);
    // Вывод исключения если не удалось создать директорию
    } else throw new \Exception('Не удалось создать директорию: '.data_dir);
}

// Инициализация класса вывода
$show = new traits\show(data_dir);