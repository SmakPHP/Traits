<?php

// Main namespace
namespace traits;

/**
 * Mail class
 *
 * Usage example:

	// Library connection
	require_once "traits/register.php";
	// An example of sending a letter in utf8 format
	$is_send = traits\mail_send::utf8(
        "email@email.ru", array("name" => "WebMaster", "email" => "email@email.ru"), "title", "message"
    );

 */
class mail_send {

	/**
	 * Email feature
	 * @param string $to send to
	 * @param array $from from whom
	 * @param string $title the headline of the message
	 * @param string $message message
	 * @return bool
	 */
	function utf8($to, $from = array("name" => "WebMaster", "email" => "email@email.ru"), $title = "", $message = "") {
		$user = "=?UTF-8?B?".base64_encode($from["name"])."?=";
		$title = "=?UTF-8?B?".base64_encode($title)."?=";
		$headers = "From: ".$user." <".$from["email"].">\r\n".
			"MIME-Version: 1.0\r\n".
			"Content-type: text/html; charset=UTF-8\r\n";
		return mail($to, $title, $message, $headers);
	}

}