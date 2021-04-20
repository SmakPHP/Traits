<?php

// Main namespace
namespace traits;

/**
 * Class to read the page
 *
 * Additionally:
 * http://php.net/manual/ru/function.curl-setopt.php
 *
 * Usage example:

  // Library connection
  require_once "traits/register.php";
  // Class initialization
  $show = new traits\show(__DIR__);

  // Initialization of the page loading class
  $pager = new traits\get_page(data);
  // Request a page
  $data = $pager->get("http://php.net");

 */
class get_page {

  /**
   * Additional headers
   * @var array
   */
  public $extra = array();

  /**
   * Received headers
   * @var string
   */
  public $header = "";

  /**
   * Received data
   * @var string
   */
  public $data = "";

  /**
   * Debug
   * @var string
   */
  private $debug = "";

  /**
   * Proxy
   * @var array
   */
  private $proxy = array("type" => "sock5", "host" => "127.0.0.1:8888");

  /**
   * Supported proxy types
   * @var array
   */
  private $types = array("sock5", "http");

  /**
   * Proxy filter
   * @var int
   */
  private $filter = "#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\:\d{2,5}$#";

  /**
   * Maximum data latency
   * @var int
   */
  private $timeout = 30;

  /**
   * Maximum number of redirects
   * @var int
   */
  private $redirect = 6;

  /**
   * Allow redirect to any host
   * @var bool
   */
  private $all = false;

  /**
   * Redirect counting
   * @var int
   */
  private $counter = 0;

  /**
   * Maximum data size
   * @var int
   */
  private $length = 0;

  /**
   * Agent
   * @var string
   */
  private $agent = "";

  /**
   * Referring page
   * @var string
   */
  private $referer = "";

  /**
   * Output directory
   * @var string
   */
  private static $path = null;

  /**
   * Class constructor
   * @param string $path output directory
   * @param array $proxy proxy
   * @param int $redirect maximum number of redirects
   * @param bool $all allow redirect to any host
   * @param int $timeout maximum data latency
   * @param bool $debug debug mode
   * @throws \Exception
   */
  public function __construct($path = "", $proxy = array(), $redirect = 6,
                              $all = false, $timeout = 30, $debug = false) {
    // Proxy validation
    if (isset($proxy["type"]) && in_array($proxy["type"], $this->types) &&
      isset($proxy["host"]) && preg_match($this->filter, $proxy["host"])) $this->proxy = $proxy;
    else $this->proxy = array();
    // Setting params
    $this->redirect = intval($redirect);
    $this->timeout = intval($timeout);
    $this->all = ($all) ? true : false;
    $this->agent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) ".
      "Chrome/85.0.4183.102 Safari/537.36";
    // Setting output directory
    self::$path = is_dir($path) ? realpath($path) : null;
    // Debug mode
    if ($debug) {
      if (!is_null(self::$path)) {
        $this->debug = self::$path."/debug.header.log";
      } else show::alert("The output directory has not been set", true);
    }
  }

  /**
   * Чтение страницы
   * @param string $link requested page
   * @param array $post submitted data
   * @param string $referer referer
   * @param string $cookie cookie
   * @param int $cleaning cleaning cookie
   * @param int $start get data from position
   * @param int $length long
   * @return bool|string
   * @throws \Exception
   */
  public function get($link, $post = array(), $referer = "", $cookie = "auto",
                      $cleaning = 8, $start = -1, $length = 20000000) {
    // Parsing source link
    $parse = parse_url($link);
    // Initialization
    $this->header = $this->data = "";
    $this->length = intval($length);
    $this->counter++;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $link);
    curl_setopt($curl, CURLOPT_USERAGENT, $this->agent);
    // Setting referer
    if ($referer == "") {
      if (strlen($this->referer)) $referer = $this->referer;
      else $referer = $parse["scheme"]."://".$parse["host"]."/";
    }
    $this->referer = $link;
    curl_setopt($curl, CURLOPT_REFERER, $referer);
    if (count($this->extra)) $headers = $this->extra; else $headers = array();
    if ($start >= 0) $headers[] = "Range: bytes=".$start."-";
    if (count($headers)) curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_HEADER, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->timeout);
    // Proxy
    if (isset($this->proxy["type"])) {
      if ($this->proxy["type"] == "sock5") {
        curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
      }
      curl_setopt($curl, CURLOPT_PROXY, $this->proxy["host"]);
    }
    curl_setopt($curl, CURLOPT_WRITEFUNCTION, array($this, "process"));
    // Send post
    if (!empty($post)) {
      curl_setopt($curl, CURLOPT_POST, true);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
    }
    // Cookie
    if (strlen($cookie)) {
      $file = ($cookie == "auto") ? "cookie.".tools::domain($parse["host"]).".txt" : $cookie;
      if (!is_null(self::$path)) {
        file_put_contents(self::$path."/".$file, "", $cleaning);
        curl_setopt($curl, CURLOPT_COOKIEFILE, self::$path."/".$file);
        curl_setopt($curl, CURLOPT_COOKIEJAR, self::$path."/".$file);
      } else show::alert("The output directory has not been set", true);
    }
    // Debug
    if (strlen($this->debug)) {
      $sniff = fopen($this->debug, "a");
      fwrite($sniff, date("Y-m-d H:i:s")." - Header:\r\n");
      curl_setopt($curl, CURLOPT_STDERR, $sniff);
      curl_setopt($curl, CURLOPT_VERBOSE, 1);
    } else $sniff = false;
    curl_exec($curl);
    if (curl_errno($curl) && strlen($this->debug)) {
      show::alert("Error: ".curl_error($curl).", while reading the page: ".$link);
    }
    curl_close($curl);
    if (is_resource($sniff)) fclose($sniff);
    // While in the data we find the service header
    while (stripos($this->data, "HTTP/1") === 0) {
      list($header, $this->data) = explode("\r\n\r\n", $this->data, 2);
      $this->header = ltrim($this->header."\r\n".$header);
    }
    // If the data is packed
    if (tools::find_reg("|Content-Encoding: (\\w+)|i", $this->header, $find) && ($start == -1)) {
      if ($find == "gzip") $this->data = gzinflate(substr($this->data, 10, -8));
      else if ($find == "deflate") $this->data = gzinflate($this->data);
    }
    // If forwarding is set
    if (tools::find_reg("|^Location: (.+)|im", $this->header, $location) && ($this->counter <= $this->redirect)) {
      // If specifying the full path
      if ((strpos($location, "http://") === 0) || (strpos($location, "https://") === 0)) {
        $host = tools::domain(parse_url($location, PHP_URL_HOST));
        if (((strpos($host, $parse["host"]) !== false) ||
            (strpos($parse["host"], $host) !== false)) || $this->all) {
          if (strlen($this->debug)) show::alert("Forwarding (1) - ".$location.", ".$this->counter);
          $this->data = $this->get($location, array(), "", $cookie, 8, $start, $length);
        } else show::alert("Cannot forward - ".$location.", host: ".$parse["host"]);
        // If it starts with a slash
      } else if ($location[0] == "/") {
        $location = $parse["scheme"]."://".$parse["host"].$location;
        if (strlen($this->debug)) show::alert("Forwarding (2) - ".$location.", ".$this->counter);
        $this->data = $this->get($location, array(), "", $cookie, 8, $start, $length);
        // If it starts with a letter
      } else if (preg_match("#^\w+#", $location)) {
        $location = $parse["scheme"]."://".$parse["host"]."/".$location;
        if (strlen($this->debug)) show::alert("Forwarding (3) - ".$location.", ".$this->counter);
        $this->data = $this->get($location, array(), "", $cookie, 8, $start, $length);
      } else show::alert("Cannot forward - ".$location);
    }
    $this->counter = 0;
    return substr($this->data, 0, $length);
  }

  /**
   * Data retrieval handler
   * @param $handle
   * @param $data
   * @return int
   */
  private function process($handle, $data) {
    $this->data .= $data;
    if (strlen($this->data) > ($this->length + 1000)) return 0; else return strlen($data);
  }

  /**
   * Short link discovery function
   * @param string $correct path for disclosure
   * @param string $link link to host
   * @param string $anchor anchor
   * @param string $info additional Information
   * @return mixed|string
   */
  public static function filter($correct, $link, &$anchor, $info = "") {
    // If there is no link
    if ($correct == "") return "";
    // Disable scripts
    if (strpos($correct, "javascript:") === 0) { $anchor = "#js"; return ""; }
    // Find anchor
    if (strpos($correct, "#") !== false) {
      list($correct, $anchor) = explode("#", $correct, 2); $anchor = "#".$anchor;
    } else $anchor = "";
    // Transform all HTML entities into matching characters.
    $correct = str_replace(" ", "%20", html_entity_decode(trim($correct)));
    // Parsing link open page
    $parse = parse_url($link);
    $host_link = $parse["scheme"]."://".$parse["host"];
    // Remove short link mode
    if (strpos($correct, "//") === 0) $correct = "http:".$correct;
    // If normal link - skip
    else if ((strpos($correct, "http://") === 0) || (strpos($correct, "https://") === 0)) {}
    // From the shortened path we do the full
    else if ($correct[0] == "/") $correct = $host_link.$correct;
    else if (strpos($correct, "./") === 0) $correct = $host_link.substr($correct, 1);
    else if (preg_match("#^[\w\?]+#", $correct)) $correct = $host_link."/".$correct;
    else if (strpos($correct, "../") === 0) {
      // Parsing document source path
      if (isset($parse["path"])) {
        $path = explode("/", $parse["path"]);
        array_shift($path); $count = count($path); if ($count) {
          if (strpos($path[$count-1], ".") !== false) array_pop($path);
        }
      } else $path = array();
      // Perform while there is a shortened path
      while (strpos($correct, "../") === 0) {
        if (strlen($correct) > 3) $correct = substr($correct, 3); else $correct = "";
        array_pop($path);
      }
      // Add the remaining file path to the end of the subfolders array
      if (strlen($correct)) array_push($path, $correct);
      // To the beginning of the array - the host link and get the result
      array_unshift($path, $host_link); $correct = implode("/", $path);
    } else {
      show::alert("Not a proper link: ".$correct.", ".$info, false, "", "nolink.txt");
      $correct = $host_link."/";
    }
    return $correct;
  }

}
