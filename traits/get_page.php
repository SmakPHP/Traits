<?php

// Основное пространство имен
namespace traits;

/**
 * Класс для чтения страницы
 *
 * Дополнительно:
 * http://php.net/manual/ru/function.curl-setopt.php
 *
 * Пример использования:

    // Подключение библиотеки
    require_once 'traits/register.php';

    // Инициализация класса вывода
    $show = new traits\show(__DIR__);

    // Инициализация класса агрузки страниц
    $pager = new traits\get_page();
    // Запрашиваем страницу
    $data = $pager->get('http://php.net');

 */
class get_page {

    /**
     * Отладка
     * @var string
     */
    private $debug = '';

    /**
     * Прокси
     * @var array
     */
    private $proxy = array('type' => 'sock5',
                           'host' => '127.0.0.1:8888');

    /**
     * Поддерживаемые типы прокси
     * @var array
     */
    private $types = array('sock5', 'http');

    /**
     * Фильтр прокси
     * @var int
     */
    private $filter = '#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\:\d{2,5}$#';

    /**
     * Максимальное время ожидания данных
     * @var int
     */
    private $timeout = 30;

    /**
     * Максимальное количество редиректов
     * @var int
     */
    private $redirect = 6;

    /**
     * Подсчет редиректов
     * @var int
     */
    private $counter = 0;

    /**
     * Максимальный размер данных
     * @var int
     */
    private $length = 0;

    /**
     * Браузер
     * @var string
     */
    private $agent = '';

    /**
     * Дополнительные заголовки
     * @var array
     */
    private $extra = array();

    /**
     * Заголовки
     * @var string
     */
    private $header = '';

    /**
     * Данные
     * @var string
     */
    private $data = '';

    /**
     * Конструктор класса
     * @param array $proxy Прокси
     * @param int $redirect Максимальное количество редиректов
     * @param int $timeout Максимальное время ожидания данных
     * @param string $debug Путь к файлу для отладки
     */
    public function __construct($proxy = array(), $redirect = 6, $timeout = 30, $debug = '') {
        // Валидация прокси
        if (isset($proxy['type']) && in_array($this->types, $proxy['type']) &&
            isset($proxy['host']) && preg_match($this->filter, $proxy['host'])) $this->proxy = $proxy;
        // Иначе просто сбрасываем
        else $this->proxy = array();
        // Установка максимальное количества редиректов
        $this->redirect = intval($redirect);
        // Установка максимального времени ожидания
        $this->timeout = intval($timeout);
        // Установка браузера
        $this->agent = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) '.
                       'Chrome/62.0.3202.62 Safari/537.36';
        // Если установлен путь к файлу для отладки
        if (strlen($this->debug) && is_writable($this->debug)) $this->debug = $debug;
    }

    /**
     * Чтение страницы
     * @param string $link Запрашиваемая страница
     * @param array $post Отправляемые данные
     * @param string $referer Рефер
     * @param string $cookie Куки
     * @param int $start Получить данные с позиции
     * @param int $length Длинной
     * @return bool|string
     */
    public function get($link, $post = array(), $referer = '', $cookie = '', $start = -1, $length = 2000000) {
        // Инициализация
        $this->header = $this->data = '';
        // Устанавливаем максимальный размер данных
        $this->length = intval($length);
        // Подсчитываем количество редиректов
        $this->counter++;
        // Парсим исходную ссылку
        $parse = parse_url($link);
        // Инициализация библиотеки
        $curl = curl_init();
        // Установка запращиваемой страницы
        curl_setopt($curl, CURLOPT_URL, $link);
        // Установка браузера
        curl_setopt($curl, CURLOPT_USERAGENT, $this->agent);
        // Если не установлена ссылающася страница
        if ($referer == '') $referer = $parse['scheme'].'://'.$parse['host'].'/';
        // Устанавливаем ссылающуюся страницу
        curl_setopt($curl, CURLOPT_REFERER, $referer);
        // Если установлены дополнительные заголовки
        if (count($this->extra)) $headers = $this->extra; else $headers = array();
        // Если установлено начало получаемой части
        if ($start >= 0) $headers[] = 'Range: bytes='.$start.'-';
        // Устанавливаем заголовки браузера
        if (count($headers)) curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        // Для чтения страниц по защищенному протоколу
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        // Возвращаем результат
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // Установка максимального времени получения данных
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        // Установка времени соединения
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        // Если использовать прокси
        if (isset($this->proxy['type'])) {
            // Выбор типа прокси
            if ($this->proxy['type'] == 'sock5') {
                // Устанавливаем тип прокси
                curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            }
            // Установка хоста прокси
            curl_setopt($curl, CURLOPT_PROXY, $this->proxy['host']);
        }
        // Ограничиваем размер загрузки
        curl_setopt($curl, CURLOPT_WRITEFUNCTION, array($this, 'process'));
        // Если отправка данных
        if (count($post)) {
            // Устанавливаем отправляемые данные
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        }
        // Если установлены куки
        if (strlen($cookie)) {
            // Создаем файл с кукаами если не существует
            if (!file_exists($cookie)) file_put_contents($cookie, '');
            // Подключаем поддержку кук
            curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie);
            curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie);
        }
        // Если отладка
        if (strlen($this->debug)) {
            // Устанавливаем файл для записи заголовов
            $sniff = fopen($this->debug, 'a');
            fwrite($sniff, date('Y-m-d H:i:s')." - Header:\r\n");
            curl_setopt($curl, CURLOPT_STDERR, $sniff);
            curl_setopt($curl, CURLOPT_VERBOSE, 1);
        // Иначе файл отладки не открыт
        } else $sniff = false;
        // Выполняем чтение страницы с установленными параметрами
        curl_exec($curl); curl_close($curl);
        // Если файл отладки открыт
        if (is_resource($sniff)) fclose($sniff);
        // Пока в полученных данных находим служебный заголовок
        while (stripos($this->data, 'HTTP/1.') === 0) {
            // Разбиваем на заголовок и данные
            list($header, $this->data) = explode("\r\n\r\n", $this->data, 2);
            // Собираем заголовок
            $this->header .= $header;
        }
        // Если данные упакованы
        if (tools::find_reg('|Content-Encoding: (\\w+)|i', $this->header, $find) && ($start == -1)) {
            // Распаковка данных из gz формата
            if ($find == 'gzip') $this->data = gzinflate(substr($this->data, 10, -8));
            // Распаковка данных из deflate формата
            else if ($find == 'deflate') $this->data = gzinflate($this->data);
        }
        // Если установленна переадресация
        if (tools::find_reg('|^Location: (.+)|im', $this->header, $location) && ($this->counter > $this->redirect)) {
            // Если указание полного пути
            if ((strpos($location, 'http://') === 0) || (strpos($location, 'https://') === 0)) {
                // Получаем хост ссылки и парсим на поддомены
                $host = parse_url($location, PHP_URL_HOST); $explode = explode('.', $host);
                // Переварачиваем массив и получаем главный хост
                $reverse = array_reverse($explode); $host = $reverse[1].'.'.$reverse[0];
                // Разрешаем переадресацию только если хосты схожы
                if ((strpos($host, $parse['host']) !== false) || (strpos($parse['host'], $host) !== false)) {
                    // Вывод в лог если отладка
                    if (strlen($this->debug)) show::alert('Переадресация (1) - '.$location.', '.$this->counter);
                    // Рекурсивное чтение страницы
                    $this->data = $this->get($location, array(), $link, $cookie, $start, $length);
                    // Иначе - выводим ошибку
                } else show::alert('В переадресации отказано - '.$location.', хост: '.$parse['host']);
            // Если начинается на слеш
            } else if ($location[0] == '/') {
                // Добавляем протокол и хост
                $location = $parse['scheme'].'://'.$parse['host'].$location;
                // Вывод в лог если отладка
                if (strlen($this->debug)) show::alert('Переадресация (2) - '.$location.', '.$this->counter);
                // Рекурсивное чтение страницы
                $this->data = $this->get($location, array(), $link, $cookie, $start, $length);
            // Если начинается на букву
            } else if (preg_match('#^\w+#', $location)) {
                // Добавляем протокол и хост
                $location = $parse['scheme'].'://'.$parse['host'].'/'.$location;
                // Вывод в лог если отладка
                if (strlen($this->debug)) show::alert('Переадресация (3) - '.$location.', '.$this->counter);
                // Рекурсивное чтение страницы
                $this->data = $this->get($location, array(), $link, $cookie, $start, $length);
            // Иначе - выводим ошибку
            } else show::alert('Не возможно переадресовать - '.$location);
        }
        // Сбрасываем счетчик редиректов
        $this->counter = 0;
        // Выводим результат
        return substr($this->data, 0, $length);
    }

    /**
     * Обработчик получения данных
     * @param $handle
     * @param $data
     * @return int
     */
    private function process($handle, $data) {
        // Получаем данные
        $this->data .= $data;
        // Если достигли заданного размера - прерываем
        if (strlen($this->data) > ($this->length + 1000)) return 0; else return strlen($data);
    }

}