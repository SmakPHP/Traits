<?php

/**
 * Функция автоматического подключения классов библиотеки
 * @param $class
 */
function register($class) {
    // Подключаем только свои библиотеки
    if (strpos($class, 'traits') === 0) {
        // Путь к подключаемому классу
        $path = dirname(__DIR__).'/'.$class.'.php';
        // Если файл существует то загружаем
        if (file_exists($path)) require_once $path;
    }
}

// Регистрируем обработчик автоматического подключения классов
spl_autoload_register('register');
