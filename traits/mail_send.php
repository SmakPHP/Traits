<?php

// Основное пространство имен
namespace traits;

/**
 * Класс почты
 *
 * Пример использования:

	// Подключение библиотеки
	require_once 'traits/register.php';

	// Пример отправки письма в формате utf8
	$is_send = traits\mail_send::utf8('sddk@list.ru', 'Admin', 'from@mail.ru', 'Title', 'Message');

 */
class mail_send {

	/**
	 * Функция отправки письма
	 * @param string $to Кому отправляем
	 * @param string $user От кого (имя отправителя)
	 * @param string $email Откуда (адрес отправителя)
	 * @param string $title Заголовок отправляемого сообщения
	 * @param string $message Отправляемое сообщение
	 * @return bool
	 */
	function utf8($to, $user, $email, $title = "", $message = "") {
		// Устанавливаем автора
		$user = "=?UTF-8?B?".base64_encode($user)."?=";
		// Устанавливаем заголовок
		$title = "=?UTF-8?B?".base64_encode($title)."?=";
		// Устанавливаем специальные заголовки
		$headers = "From: ".$user." <".$email.">\r\n".
			"MIME-Version: 1.0\r\n".
			"Content-type: text/html; charset=UTF-8\r\n";
		// Отправка письма
		return mail($to, $title, $message, $headers);
	}

}