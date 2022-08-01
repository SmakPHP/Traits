# Traits

Библиотека кода с автоматическим подключением

Основные задачи:
1. Подборка полезных классов, которые упрощают работу
2. Поиск наиболее оптимальных решений с упором на производительность

Пример использования:

    // Подключение библиотеки
    require_once 'traits/register.php';
    
    // Чтение страницы
    $proxy = array('type' => 'http', 'host' => '127.0.0.1:8888');
    // Инициализация класса загрузки страниц
    $pager = new traits\get_page(data_dir, $proxy);
    // Запрашиваем страницу
    $data = $pager->get('http://php.net');
    
    // Сборка XML документа
    $xml = traits\array_xml::extent('inf')->build(array('key' => 'value'));
    
Больше примеров в example.php

WhatsApp: +79773904520, skype: SmakPHP  
Telegram: https://t.me/AndreyMt2
