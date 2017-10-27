<?php

// Основное пространство имен
namespace traits;

/**
 * Шлюз взаимодействия с soap сервером
 * Необходимый базис для передачи результата
 */
class soap_gate {

    /**
     * Сохранение результата
     * @var array
     */
    protected $result = array();

    /**
     * Вывод результата
     * @return array
     */
    public function GetResult() {
        return $this->result;
    }

}