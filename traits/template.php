<?php

// Main namespace
namespace traits;

/**
 * Quick template class
 *
 * Usage example:

  $tp = new traits\template("index.html");
  $tp->set("body", "text");
  echo $tp->result;

 */
class template {

  /**
   * Template text
   * @var bool|string
   */
  public $result = "";

  /**
   * Loading template
   * @param $path
   */
  public function __construct($path) {
    if (strlen($path)) {
      if (!preg_match("#[/\\\]+#", $path)) $path = show."/".$path;
      if (file_exists($path)) $this->result = files::read_file($path);
      else show::alert("Template file not found: ".$path, true);
    } else show::alert("Need to set the path to the template", true);
  }

  /**
   * Setting value
   * @param $name
   * @param $value
   */
  public function set($name, $value) {
    $this->result = str_replace("{".$name."}", $value, $this->result);
  }

}