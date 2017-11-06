<?php

/**
 * Использование библиотеки
 */
// Подключение библиотеки
require_once 'traits/register.php';

/**
 * Чтение страницы
 */
/*
// Использование прокси
$proxy = array('type' => 'http', 'host' => '127.0.0.1:8888');
// Инициализация класса загрузки страниц
$pager = new traits\get_page(data_dir, $proxy, 3, true, 20, true);
// Запрашиваем страницу
$data = $pager->get('http://php.net');
*/

/**
 * Пример создания массива из структурированного класса
 */
// Подключение класса - библиотеки
require_once 'traits/restrict.php';
// Создание вложенного элемента
$include = new restrict\statusItem();
// Установка значения элемента
$include->add('status', 'value');
// Автоматическая установка элемента согласно названия класса
// Если родительский класс поддерживает массив данных элементов
// то можно отправить несколько однотипных элементов
$organization = new restrict\organization($include, $include);
// Преобразуем в массив
$result = $organization->result();

/*
 * Array
(
    [statusItem] => Array
        (
            [0] => Array
                (
                    [status] => value
                    [updateDate] =>
                )

            [1] => Array
                (
                    [status] => value
                    [updateDate] =>
                )

        )

)
 */

// Для отладки
die();
