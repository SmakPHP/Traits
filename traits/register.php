<?php

/**
 * Library class auto-connect feature
 * @param $class
 */
function register($class) {
	// We connect only our libraries
	if (strpos($class, "traits") === 0) {
		$path = dirname(__DIR__)."/".str_replace("\\", "/", $class).".php";
		if (file_exists($path)) require_once $path;
	}
}

// Registering the class automatic connection handler
spl_autoload_register("register");

// Install directories
if (!defined("root")) define("root", dirname(__DIR__));
if (!defined("data")) define("data", root."/data");
if (!defined("show")) define("show", root."/show/");

// Create an output directory if necessary
if (!file_exists(data)) {
	if (mkdir(data, true)) chmod(data, 0777);
  else throw new \Exception("Could not create data directory: ".data);
}

// Create an template directory if necessary
if (!file_exists(show)) {
  if (mkdir(show, true)) chmod(show, 0777);
  else throw new \Exception("Could not create template directory: ".show);
}

// Class initialization
$show = new traits\show(data);
$cache = new traits\cache(data);
