<?php

// Основное пространство имен
namespace traits;

/**
 * Драйвер для работы с soap сервером, для модификации отправляемого документа
 *
 * Грабли:
 * 1. Не возможности в самом SoapServer перехватывать Header (фича php)
 *
 * Пример использования:

    // Подключение библиотеки
    require_once 'traits/register.php';

    // Примерный шлюз для взаимодействия
    class soap_example extends traits\soap_gate {

        // Название вызываемого метода
        public function ExampleMethod() {
            // Просто возвращаем отправленные аргументы
            return func_get_args();
        }
    }

    // Отключаем кеширование wsdl
    ini_set('soap.wsdl_cache_enabled', '0');

    // Безопасный блок
    try {
        // Включаем буферизацию вывода
        ob_start();
        // Создаем новый soap сервер (no wsdl)
        $server = new SoapServer(null, array(
            'uri' => 'http://example.com/gate'
        ));
        // Инициализируем класс для подписи документа
        $sign = new traits\sign_xml('tmp/name.crt', 'tmp/name.pem');
        // Инициализируем драйвер soap шлюза
        $engine = new traits\soap_engine(new soap_example(), $sign);
        // Проверка подписи запроса
        // $engine->check($input);
        // Регистрируем обработчик
        $server->setObject($engine);
        // Обработка soap запроса
        $server->handle();
        // Получаем данные
        $soap = ob_get_contents();
        // Сброс буфера вывода
        ob_end_clean();

        // Получаем результат от шлюза и собираем документ с пространством имен inf
        $xml_response = traits\array_xml::extent('inf')->build($engine->get_result());

        ...

    // Если произошла ошибка
    } catch (SoapFault $exception) {
        // Вывод ошибки
        echo $exception->getMessage();
    }

*/
class soap_engine {

    /**
     * Шлюз взаимодействия
     * @var null|soap_gate
     */
    private $gate = null;

    /**
     * Последний вызванный метод
     * @var string
     */
    private $method = '';

    /**
     * Сохранение заголовков
     * @var array
     */
    public $sign = null;

    /**
     * Логирование
     * @var string
     */
    public $log = '';

    /**
     * Конструктор драйвера
     * @param soap_gate $gate Объект шлюза
     * @param sign_xml $sign Объект подписи
     */
    public function __construct(soap_gate $gate, sign_xml $sign) {
        // Если передан экземпляр шлюза
        if ($gate instanceof soap_gate) {
            // Устанавливаем шлюз взамодействия
            $this->gate = $gate;
        }
        // Если передан экземляр объекта подписи
        if ($sign instanceof sign_xml) {
            // Устанавливаем класс подписи документа
            $this->sign = $sign;
        }
    }

    /**
     * Подписывание документа
     * @param string $data Данные для создания сигнатуры
     * @param bool $debug Режим создания сигнатуры
     */
    public function check($data = '', $debug = false) {
        // Если необходимо проверить валидность подпись запроса
        if ($this->sign instanceof sign_xml) {
            // Создаем документ
            $dom = new xml_dom($data);
            // Если режим создания сигнатуры подписи
            if ($debug) {
                // Подписываем и отправляем документ
                $signature = $this->sign->signature($dom);
                // Выводим предупреждение
                $this->alert('Сигнатура для данного запроса: '.$signature);
            } else {
                // Поиск текста сигнатуры
                $data = $dom->get('SignedInfo')->C14N(true);
                // Поиск ключа сигнатуры
                $key = $dom->get('SignatureValue')->nodeValue;
                // Проверка подписи документа
                if (!$this->sign->validate($data, $key)) {
                    // Выводим предупреждение
                    $this->alert('Подпись документа не корректна!');
                }
            }
        }
    }

    /**
     * Подписывание документа
     * @param string $xml Документ который необходимо подписать
     * @return string
     */
    public function sign($xml) {
        // Инициализация
        $result = '';
        // Если установлен класс подписи документа
        if ($this->sign instanceof sign_xml) {
            // Вызываем метод из класса подписи документа
            $result = $this->sign->sign($xml);
            // Копируем лог от класса подписи документа плюс добавляем текст ошибки
            $this->log = $this->sign->log;
        }
        // Вывод результата
        return $result;
    }

    /**
     * Вывод предупреждения
     * @param $message
     * @throws \Exception
     */
    private function alert($message) {
        // Если установлен класс подписи документа
        if ($this->sign instanceof sign_xml) {
            // Копируем лог от класса подписи документа плюс добавляем текст ошибки
            $this->log = $this->sign->log."Ошибка: ".$message."\r\n";
        }
        // Выбрасываем исключение
        throw new \Exception($message);
    }

    /**
     * Выполнение метода шлюза
     * @param string $method Вызываемый метод
     * @param array $params Массив с передаваемыми параметрами
     * @return mixed
     * @throws \Exception
     */
    public function __call($method, $params) {
        // Инициализация
        $result = '';
        // Сохранение вызываемого метода
        $this->method = $method;
        // Если метод поддерживается в шлюзе
        if (method_exists($this->gate, $method)) {
            // Выполняем пользовательский метод в шлюзе
            $result = call_user_func_array(array($this->gate, $method), $params);
        } else {
            // Выбрасываем исключение
            $this->alert('Не найден метод: '.$method.' в шлюзе: '.get_class($this->gate));
        }
        // Вывод результата
        return $result;
    }

    /**
     * Вывод последнего вызванного метода
     * @return string
     */
    public function get_method() {
        // Возвращаем название метода
        return $this->method;
    }

    /**
     * Вывод результата
     * @return array
     */
    public function get_result() {
        // Если шлюз установлен то выводим результат, иначе пустой массив
        return (!is_null($this->gate)) ? $this->gate->GetResult() : array();
    }
}
