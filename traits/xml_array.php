<?php

// Основное пространство имен
namespace traits;

/**
 * Класс преобразования xml документа в массив
 * В основе используется XML Parser
 *
 * Грабли:
 * 1. Не верно обрабатывает повторяющиеся теги (фича php)
 *
 * Пример использования:

  // Парсим запрос
  $parse = traits\xml_array::parse($input);

  // Поиск значений
  $security = $parse->find_tag('WSSE:BINARYSECURITYTOKEN');
  $signature = $parse->find_tag('SIGNATUREVALUE');
  $digest = $parse->find_tag('DIGESTVALUE');

 */
class xml_array {

  /**
   * Временный буфер
   * @var array
   */
  public $buffer = array();

  /**
   * Результат парсинга
   * @var array
   */
  public $result = array();

  /**
   * Экземляр XML Parser
   * @var
   */
  private $handle;

  /**
   * Трассировка поиска
   * @var string
   */
  public $tracert = '';

  /**
   * Конструктор класса, парсинг документа
   * @param $data
   * @throws \Exception
   */
  public function __construct($data) {
    // Создание xml анализатора
    $this->handle = xml_parser_create();
    // Установка класса обработчика
    xml_set_object($this->handle, $this);
    // Установка методов
    xml_set_element_handler($this->handle, "tagOpen", "tagClosed");
    xml_set_character_data_handler($this->handle, "tagData");
    // Если произошла ошибка при парсинге
    if (!xml_parse($this->handle, $data)) {
      // Выбрасываем исключение
      throw new \Exception(
        sprintf(
          "Ошибка парсинга документа: %s строка %d",
          xml_error_string(xml_get_error_code($this->handle)),
          xml_get_current_line_number($this->handle)
        )
      );
    }
    // Освобождение ресурсов
    xml_parser_free($this->handle);
  }

  /**
   * Загрузка данных и парсинг
   * @param string $data
   * @return xml_array
   */
  public static function parse($data = '') {
    // Возвращаем текущий экземляр класса
    return new self($data);
  }

  /**
   * Обработка открывающего тега
   * @param $parser
   * @param $name
   * @param $attributes
   */
  private function tagOpen($parser, $name, $attributes) {
    // Переводим в нижний регистр
    $name = strtolower($name);
    // Параметры элемента в виде массива
    $tag = array('name' => $name, 'attributes' => $attributes);
    // Добавляем во временный буфер
    array_push($this->buffer, $tag);
    // Получаем количество вложенных элементов
    $count = count($this->buffer);
    // Инициализация
    $current = 0; $result =& $this->result;
    // Перебираем структуру вложенности
    foreach ($this->buffer as $id => $append) {
      // Идентификатор вложенности
      $current++;
      // Если дошли до последнего
      if ($count == $current) {
        // Если один атрибут
        if (count($attributes) == 1) {
          // Перебираем атрибуты
          foreach ($attributes as $key => $value) {
            // Получаем название атрибута
            $key = '@'.strtolower($key);
            // Если нет или не является массивом
            if (!isset($result[$name][$key]) || !is_array($result[$name][$key])) {
              // Устанавливаем значение атрибута
              $result[$name][$key] = $value;
            }
          }
        }
      // Иначе поднимаемся на уровень выше
      } else {
        // Если есть название элемента
        if (isset($append['name'])) {
          // Если есть вложенный с таким именем просто переходит к нему
          if (isset($result[$append['name']])) $result =& $result[$append['name']];
          // Иначе создаем новый пустой элемент и переходим к нему
          else {
            $result[$append['name']] = array();
            $result =& $result[$append['name']];
          }
        }
      }
    }
  }

  /**
   * Обработка данных
   * @param $parser
   * @param $tagData
   */
  private function tagData($parser, $tagData) {
    if (trim($tagData)) {
      $count_id = count($this->buffer);
      if (isset($this->buffer[$count_id - 1]['data'])) {
        $this->buffer[$count_id - 1]['data'] .= $tagData;
      } else {
        $this->buffer[$count_id - 1]['data'] = $tagData;
      }
    }
  }

  /**
   * Обработка закрывающего тега
   * @param $parser
   * @param $name
   */
  private function tagClosed($parser, $name) {
    // Получаем количество вложенных элементов
    $count = count($this->buffer);
    // Последний вложенный элемент
    $tag = $this->buffer[$count - 1];
    // Вкладываем в предпоследний
    $this->buffer[$count - 2]['children'][] = $tag;
    // Инициализация
    $current = 0; $result =& $this->result;
    // Перебираем структуру вложенности
    foreach ($this->buffer as $id => $append) {
      // Идентификатор вложенности
      $current++;
      // Если дошли до последнего
      if ($count == $current) {
        // Получаем данные для элемента
        $data = (isset($tag['data'])) ? $tag['data'] : '';
        // Получаем название элемента
        $name = strtolower($tag['name']);
        // Если нет или не является массивом
        if (!isset($result[$name]) || !is_array($result[$name])) {
          // Устанавливаем значение атрибута
          $result[$name] = $data;
        }
/*
        // Если существует элемент с таким именем
        if (isset($result[$name])) {
          // Если элемент текущий является массивом
          if (is_array($result[$name])) $result[$name][] = $data;
          // Иначе просто добавляем
          else {
            // Если добавляемый элемент массив делаем вложенность
            if (is_array($data)) {
              $result[$name][] = $result[$name];
              $result[$name][] = $data;
            // Иначе просто добавляем элемент
            } else $result[$name] = $data;
          }
        // Если не существует элемента
        } else {
          // Если добавляемый элемент массив делаем вложенность
          if (is_array($data)) $result[$name][] = $data;
          // Иначе просто добавляем элемент
          else $result[$name] = $data;
        }
*/
      // Иначе поднимаемся на уровень выше
      } else {
        // Если есть название элемента
        if (isset($append['name'])) {
          // Если есть вложенный с таким именем просто переходит к нему
          if (isset($result[$append['name']])) $result =& $result[$append['name']];
          // Иначе создаем новый пустой элемент и переходим к нему
          else {
            $result[$append['name']] = array();
            $result =& $result[$append['name']];
          }
        }
      }
    }
    // Удаляем последний
    array_pop($this->buffer);
  }

  /**
   * Поиск по имени
   * @param $name
   * @param array $haystack
   * @return string
   */
  public function find_tag($name, $haystack = array('start' => true)) {
    // Инициализация
    $result = '';
    // Если первый запуск
    if (isset($haystack['start'])) {
      // Устанавливаем список элементов из парсинга
      $nodes = $this->buffer;
      // Сброс буфера отладки поиска
      $this->tracert = '';
      // Иниаче просто копируем список элементов
    } else $nodes = $haystack;
    // Выполняем перебор элементов
    foreach ($nodes as $children) {
      // Если есть название тега
      if (isset($children['name'])) {
        // Добавляем в журнал трассировки поиска
        $this->tracert .= $children['name']."\r\n";
        // Если есть значение тега и название совпадает с искомым
        if (isset($children['tagData']) && $children['name'] == $name) {
          // Получаем значение
          $result = $children['tagData'];
        // Если есть вложенный элемент
        } elseif (isset($children['children'])) {
          // Выполняем рекурсивный поиск
          $result = $this->find_tag($name, $children['children']);
        }
        // Если найдено то прерываем
        if (strlen($result)) break;
      }
    }
    // Вывод результата
    return $result;
  }

  /**
   * Вывод результата
   * @return array
   */
  public function result() {
    return $this->buffer;
  }

}
