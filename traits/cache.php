<?php

// Main namespace
namespace traits;

/**
 * Quick cache class
 *
 * Usage example:
 */
class cache {

  /**
   * Cache directory
   * @var string
   */
  private static $path = null;

  /**
   * Class constructor
   * @param string $path Директория вывода
   */
  public function __construct($path) {
    self::$path = is_dir($path) ? realpath($path) : null;
  }

  /**
   * Get var from cache
   * @param $name
   * @param string
   * @return array|mixed
   */
  public static function get($name, $type = "string") {
    $cached = "";
    if (!is_null(self::$path)) {
      $name = self::$path."/cache_".$name.".txt";
      if (file_exists($name)) {
        $cached = unserialize(file_get_contents($name));
      }
    }
    if ($type == "array") {
      if (is_array($cached)) return $cached;
      else if (!empty($cached)) return array(strval($cached));
      else return array();
    } else {
      return strval($cached);
    }
  }

  /**
   * Set var to cache
   * @param $name
   * @param $value
   */
  public static function set($name, $value) {
    if (!is_null(self::$path)) {
      $name = self::$path."/cache_".$name.".txt";
      file_put_contents($name, serialize($value));
    }
  }

}

// Reset file cache
clearstatcache();