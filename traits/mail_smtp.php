<?php

// Main namespace
namespace traits;

/**
 * Mail class
 *
 * Usage example:

  // Library connection
  require_once "traits/register.php";
  // An example of sending a letter via smtp
  $is_send = traits\mail_smtp::send_smtp(
    "email@email.ru", array("name" => "WebMaster", "email" => "email@email.ru"),
    "title", "message", "127.0.0.1", 25, "admin", "password"
  );

 */
class mail_smtp {

  /**
   * Sending a letter via smtp server
   * @param string $to send to
     * @param array $from from whom
   * @param string $title the headline of the message
   * @param string $message message
     * @param string $host smtp host
     * @param int $port smtp port
   * @param string $user login from smtp server
   * @param string $password password from smtp server
   * @param string $file file to send
   * @return bool
   */
  public static function send_smtp($to, $from = array("name" => "WebMaster", "email" => "email@email.ru"),
                                   $title, $message, $host = "127.0.0.1", $port = 25, $user, $password, $file = "") {
    $mail = new mailer;
    // For debugging
    // 0 = off (for production use)
    // 1 = client messages
    // 2 = client and server messages
    $mail->smtp_debug = 0;
    $mail->debugoutput = "html";
    // Disable encryption
    $mail->smtp_secure = "";
    $mail->smtp_auto_tls = false;
    // Setting phpmailer
    $mail->is_smtp();
    $mail->host = $host;
    $mail->port = $port;
    $mail->smtp_auth = true;
    $mail->username = $user;
    $mail->password = $password;
    $mail->set_from($from["email"], $from["name"]);
    $mail->add_address($to);
    $mail->subject = $title;
    $mail->body = $message;
    if (strlen($file)) $mail->add_attachment($file, "", "binary");
    return $mail->send();
  }

}

/**
 * PHPMailer RFC821 SMTP email transport class.
 * Implements RFC 821 SMTP commands and provides some 
 * utility methods for sending mail to an SMTP server.
 */
class smtp {

    /**
     * The PHPMailer SMTP version number.
     * @var string
     */
    const version = '5.2.23';

    /**
     * SMTP line break constant.
     * @var string
     */
    const crlf = "\r\n";

    /**
     * The SMTP port to use if one is not specified.
     * @var integer
     */
    const default_smtp_port = 25;

    /**
     * The maximum line length allowed by RFC 2822 section 2.1.1
     * @var integer
     */
    const max_line_length = 998;

    /**
     * Debug level for no output
     */
    const debug_off = 0;

    /**
     * Debug level to show client -> server messages
     */
    const debug_client = 1;

    /**
     * Debug level to show client -> server and server -> client messages
     */
    const debug_server = 2;

    /**
     * Debug level to show connection status, client -> server and server -> client messages
     */
    const debug_connection = 3;

    /**
     * Debug level to show all messages
     */
    const debug_lowlevel = 4;

    /**
     * The PHPMailer SMTP Version number.
     * @var string
     * @deprecated Use the `VERSION` constant instead
     * @see smtp::version
     */
    public $version = '5.2.23';

    /**
     * SMTP server port number.
     * @var integer
     * @deprecated This is only ever used as a default value, so use the `DEFAULT_SMTP_PORT` constant instead
     * @see smtp::default_smtp_port
     */
    public $smtp_port = 25;

    /**
     * SMTP reply line ending.
     * @var string
     * @deprecated Use the `CRLF` constant instead
     * @see smtp::crlf
     */
    public $crlf = "\r\n";

    /**
     * Debug output level.
     * Options:
     * * self::DEBUG_OFF (`0`) No debug output, default
     * * self::DEBUG_CLIENT (`1`) Client commands
     * * self::DEBUG_SERVER (`2`) Client commands and server responses
     * * self::DEBUG_CONNECTION (`3`) As DEBUG_SERVER plus connection status
     * * self::DEBUG_LOWLEVEL (`4`) Low-level data output, all messages
     * @var integer
     */
    public $do_debug = self::debug_off;

    /**
     * How to handle debug output.
     * Options:
     * * `echo` Output plain-text as-is, appropriate for CLI
     * * `html` Output escaped, line breaks converted to `<br>`, appropriate for browser output
     * * `error_log` Output to error log as configured in php.ini
     *
     * Alternatively, you can provide a callable expecting two params: a message string and the debug level:
     * <code>
     * $smtp->Debugoutput = function($str, $level) {echo "debug level $level; message: $str";};
     * </code>
     * @var string|callable
     */
    public $debugoutput = 'echo';

    /**
     * Whether to use VERP.
     * @link http://en.wikipedia.org/wiki/Variable_envelope_return_path
     * @link http://www.postfix.org/VERP_README.html Info on VERP
     * @var boolean
     */
    public $do_verp = false;

    /**
     * The timeout value for connection, in seconds.
     * Default of 5 minutes (300sec) is from RFC2821 section 4.5.3.2
     * This needs to be quite high to function correctly with hosts using greetdelay as an anti-spam measure.
     * @link http://tools.ietf.org/html/rfc2821#section-4.5.3.2
     * @var integer
     */
    public $timeout = 300;

    /**
     * How long to wait for commands to complete, in seconds.
     * Default of 5 minutes (300sec) is from RFC2821 section 4.5.3.2
     * @var integer
     */
    public $timelimit = 300;

    /**
     * @var array patterns to extract smtp transaction id from smtp reply
     * Only first capture group will be use, use non-capturing group to deal with it
     * Extend this class to override this property to fulfil your needs.
     */
    protected $smtp_transaction_id_patterns = array(
        'exim' => '/[0-9]{3} OK id=(.*)/',
        'sendmail' => '/[0-9]{3} 2.0.0 (.*) Message/',
        'postfix' => '/[0-9]{3} 2.0.0 Ok: queued as (.*)/'
    );

    /**
     * The socket for the server connection.
     * @var resource
     */
    protected $smtp_conn;

    /**
     * Error information, if any, for the last SMTP command.
     * @var array
     */
    protected $error = array(
        'error' => '',
        'detail' => '',
        'smtp_code' => '',
        'smtp_code_ex' => ''
    );

    /**
     * The reply the server sent to us for HELO.
     * If null, no HELO string has yet been received.
     * @var string|null
     */
    protected $helo_rply = null;

    /**
     * The set of SMTP extensions sent in reply to EHLO command.
     * Indexes of the array are extension names.
     * Value at index 'HELO' or 'EHLO' (according to command that was sent)
     * represents the server name. In case of HELO it is the only element of the array.
     * Other values can be boolean TRUE or an array containing extension options.
     * If null, no HELO/EHLO string has yet been received.
     * @var array|null
     */
    protected $server_caps = null;

    /**
     * The most recent reply received from the server.
     * @var string
     */
    protected $last_reply = '';

    /**
     * Output debugging info via a user-selected method.
     * @see smtp::$debugoutput
     * @see smtp::$do_debug
     * @param string $str Debug string to output
     * @param integer $level The debug level of this message; see DEBUG_* constants
     * @return void
     */
    protected function edebug($str, $level = 0) {
        if ($level > $this->do_debug) {
            return;
        }
        // Avoid clash with built-in function names
        if (!in_array($this->debugoutput, array('error_log', 'html', 'echo')) and is_callable($this->debugoutput)) {
            call_user_func($this->debugoutput, $str, $level);
            return;
        }
        switch ($this->debugoutput) {
            case 'error_log':
                // Don't output, just log
                error_log($str);
                break;
            case 'html':
                // Cleans up output a bit for a better looking, HTML-safe output
                echo gmdate('Y-m-d H:i:s').' '.htmlentities(
                    preg_replace('/[\r\n]+/', '', $str), ENT_QUOTES, 'UTF-8'
                )."<br>\n";
                break;
            case 'echo':
            default:
                // Normalize line breaks
                $str = preg_replace('/(\r\n|\r|\n)/ms', "\n", $str);
                echo gmdate('Y-m-d H:i:s')."\t".str_replace(
                    "\n", "\n                   \t                  ", trim($str)
                )."\n";
        }
    }

    /**
     * Connect to an SMTP server.
     * @param string $host SMTP server IP or host name
     * @param integer $port The port number to connect to
     * @param integer $timeout How long to wait for the connection to open
     * @param array $options An array of options for stream_context_create()
     * @access public
     * @return boolean
     */
    public function connect($host, $port = null, $timeout = 30, $options = array()) {
        static $streamok;
        // This is enabled by default since 5.0.0 but some providers disable it
        // Check this once and cache the result
        if (is_null($streamok)) {
            $streamok = function_exists('stream_socket_client');
        }
        // Clear errors to avoid confusion
        $this->set_error('');
        // Make sure we are __not__ connected
        if ($this->connected()) {
            // Already connected, generate error
            $this->set_error('Already connected to a server');
            return false;
        }
        if (empty($port)) {
            $port = self::default_smtp_port;
        }
        // Connect to the SMTP server
        $this->edebug(
            "Connection: opening to $host:$port, timeout=$timeout, options=" .
            var_export($options, true), self::debug_connection
        );
        $errno = 0; $errstr = '';
        if ($streamok) {
            $socket_context = stream_context_create($options);
            set_error_handler(array($this, 'error_handler'));
            $this->smtp_conn = stream_socket_client(
                $host.":".$port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $socket_context
            );
            restore_error_handler();
        } else {
            //Fall back to fsockopen which should work in more places, but is missing some features
            $this->edebug(
                "Connection: stream_socket_client not available, falling back to fsockopen",
                self::debug_connection
            );
            set_error_handler(array($this, 'error_handler'));
            $this->smtp_conn = fsockopen(
                $host, $port, $errno, $errstr, $timeout
            );
            restore_error_handler();
        }
        // Verify we connected properly
        if (!is_resource($this->smtp_conn)) {
            $this->set_error('Failed to connect to server', $errno, $errstr);
            $this->edebug('SMTP ERROR: '.$this->error['error'].": $errstr ($errno)", self::debug_client);
            return false;
        }
        $this->edebug('Connection: opened', self::debug_connection);
        // SMTP server can take longer to respond, give longer timeout for first read
        // Windows does not have support for this timeout function
        if (substr(PHP_OS, 0, 3) != 'WIN') {
            $max = ini_get('max_execution_time');
            // Don't bother if unlimited
            if ($max != 0 && $timeout > $max) {
                @set_time_limit($timeout);
            }
            stream_set_timeout($this->smtp_conn, $timeout, 0);
        }
        // Get any announcement
        $announce = $this->get_lines();
        $this->edebug('SERVER -> CLIENT: '.$announce, self::debug_server);
        return true;
    }

    /**
     * Initiate a TLS (encrypted) session.
     * @access public
     * @return boolean
     */
    public function start_tls() {
        if (!$this->send_command('STARTTLS', 'STARTTLS', 220)) {
            return false;
        }
        // Allow the best TLS version(s) we can
        $crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        // PHP 5.6.7 dropped inclusion of TLS 1.1 and 1.2 in STREAM_CRYPTO_METHOD_TLS_CLIENT
        // so add them back in manually if we can
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
        }
        // Begin encrypted connection
        set_error_handler(array($this, 'error_handler'));
        $crypto_ok = stream_socket_enable_crypto($this->smtp_conn, true, $crypto_method);
        restore_error_handler();
        return $crypto_ok;
    }

    /**
     * Perform SMTP authentication.
     * Must be run after hello().
     * @see hello()
     * @param string $username The user name
     * @param string $password The password
     * @param string $authtype The auth type (PLAIN, LOGIN, CRAM-MD5)
     * @return bool True if successfully authenticated.* @access public
     */
    public function authenticate($username, $password, $authtype = null) {
        if (!$this->server_caps) {
            $this->set_error('Authentication is not allowed before HELO/EHLO');
            return false;
        }
        if (array_key_exists('EHLO', $this->server_caps)) {
            // SMTP extensions are available; try to find a proper authentication method
            if (!array_key_exists('AUTH', $this->server_caps)) {
                $this->set_error('Authentication is not allowed at this stage');
                // 'at this stage' means that auth may be allowed after the stage changes
                // e.g. after STARTTLS
                return false;
            }
            self::edebug('Auth method requested: '.($authtype ? $authtype : 'UNKNOWN'), self::debug_lowlevel);
            self::edebug(
                'Auth methods available on the server: '.implode(',', $this->server_caps['AUTH']),
                self::debug_lowlevel
            );
            if (empty($authtype)) {
                foreach (array('CRAM-MD5', 'LOGIN', 'PLAIN') as $method) {
                    if (in_array($method, $this->server_caps['AUTH'])) {
                        $authtype = $method;
                        break;
                    }
                }
                if (empty($authtype)) {
                    $this->set_error('No supported authentication methods found');
                    return false;
                }
                self::edebug('Auth method selected: '.$authtype, self::debug_lowlevel);
            }
            if (!in_array($authtype, $this->server_caps['AUTH'])) {
                $this->set_error("The requested authentication method \"$authtype\" is not supported by the server");
                return false;
            }
        } elseif (empty($authtype)) {
            $authtype = 'LOGIN';
        }
        switch ($authtype) {
            case 'PLAIN':
                // Start authentication
                if (!$this->send_command('AUTH', 'AUTH PLAIN', 334)) {
                    return false;
                }
                // Send encoded username and password
                if (!$this->send_command('User & Password', base64_encode("\0".$username."\0".$password), 235)) {
                    return false;
                }
                break;
            case 'LOGIN':
                // Start authentication
                if (!$this->send_command('AUTH', 'AUTH LOGIN', 334)) {
                    return false;
                }
                if (!$this->send_command("Username", base64_encode($username), 334)) {
                    return false;
                }
                if (!$this->send_command("Password", base64_encode($password), 235)) {
                    return false;
                }
                break;
            case 'CRAM-MD5':
                // Start authentication
                if (!$this->send_command('AUTH CRAM-MD5', 'AUTH CRAM-MD5', 334)) {
                    return false;
                }
                // Get the challenge
                $challenge = base64_decode(substr($this->last_reply, 4));
                // Build the response
                $response = $username.' '.$this->hmac($challenge, $password);
                // Send encoded credentials
                return $this->send_command('Username', base64_encode($response), 235);
            default:
                $this->set_error("Authentication method \"$authtype\" is not supported");
                return false;
        }
        return true;
    }

    /**
     * Calculate an MD5 HMAC hash.
     * Works like hash_hmac('md5', $data, $key)
     * in case that function is not available
     * @param string $data The data to hash
     * @param string $key The key to hash with
     * @access protected
     * @return string
     */
    protected function hmac($data, $key) {
        if (function_exists('hash_hmac')) {
            return hash_hmac('md5', $data, $key);
        }
        // The following borrowed from
        // http://php.net/manual/en/function.mhash.php#27225
        // RFC 2104 HMAC implementation for php.
        // Creates an md5 HMAC.
        // Eliminates the need to install mhash to compute a HMAC
        // by Lance Rushing
        $bytelen = 64; // byte length for md5
        if (strlen($key) > $bytelen) {
            $key = pack('H*', md5($key));
        }
        $key = str_pad($key, $bytelen, chr(0x00));
        $ipad = str_pad('', $bytelen, chr(0x36));
        $opad = str_pad('', $bytelen, chr(0x5c));
        $k_ipad = $key ^ $ipad;
        $k_opad = $key ^ $opad;
        return md5($k_opad.pack('H*', md5($k_ipad.$data)));
    }

    /**
     * Check connection state.
     * @access public
     * @return boolean True if connected.
     */
    public function connected() {
        if (is_resource($this->smtp_conn)) {
            $sock_status = stream_get_meta_data($this->smtp_conn);
            if ($sock_status['eof']) {
                // The socket is valid but we are not connected
                $this->edebug('SMTP NOTICE: EOF caught while checking if connected', self::debug_client);
                $this->close();
                return false;
            }
            return true; // everything looks good
        }
        return false;
    }

    /**
     * Close the socket and clean up the state of the class.
     * Don't use this function without first trying to use QUIT.
     * @see quit()
     * @access public
     * @return void
     */
    public function close() {
        $this->set_error('');
        $this->server_caps = null;
        $this->helo_rply = null;
        if (is_resource($this->smtp_conn)) {
            // Close the connection and cleanup
            fclose($this->smtp_conn);
            $this->smtp_conn = null; //Makes for cleaner serialization
            $this->edebug('Connection: closed', self::debug_connection);
        }
    }

    /**
     * Send an SMTP DATA command.
     * Issues a data command and sends the msg_data to the server,
     * finializing the mail transaction. $msg_data is the message
     * that is to be send with the headers. Each header needs to be
     * on a single line followed by a <CRLF> with the message headers
     * and the message body being separated by and additional <CRLF>.
     * Implements rfc 821: DATA <CRLF>
     * @param string $msg_data Message data to send
     * @access public
     * @return boolean
     */
    public function data($msg_data) {
        // This will use the standard timelimit
        if (!$this->send_command('DATA', 'DATA', 354)) {
            return false;
        }
        /* The server is ready to accept data!
         * According to rfc821 we should not send more than 1000 characters on a single line (including the CRLF)
         * so we will break the data up into lines by \r and/or \n then if needed we will break each of those into
         * smaller lines to fit within the limit.
         * We will also look for lines that start with a '.' and prepend an additional '.'.
         * NOTE: this does not count towards line-length limit.
         */
        // Normalize line breaks before exploding
        $lines = explode("\n", str_replace(array("\r\n", "\r"), "\n", $msg_data));
        /* To distinguish between a complete RFC822 message and a plain message body, we check if the first field
         * of the first line (':' separated) does not contain a space then it _should_ be a header and we will
         * process all lines before a blank line as headers.
         */
        $field = substr($lines[0], 0, strpos($lines[0], ':'));
        $in_headers = false;
        if (!empty($field) && strpos($field, ' ') === false) {
            $in_headers = true;
        }
        foreach ($lines as $line) {
            $lines_out = array();
            if ($in_headers and $line == '') {
                $in_headers = false;
            }
            // Break this line up into several smaller lines if it's too long
            // Micro-optimisation: isset($str[$len]) is faster than (strlen($str) > $len),
            while (isset($line[self::max_line_length])) {
                // Working backwards, try to find a space within the last MAX_LINE_LENGTH chars of the line to break on
                // so as to avoid breaking in the middle of a word
                $pos = strrpos(substr($line, 0, self::max_line_length), ' ');
                // Deliberately matches both false and 0
                if (!$pos) {
                    // No nice break found, add a hard break
                    $pos = self::max_line_length - 1;
                    $lines_out[] = substr($line, 0, $pos);
                    $line = substr($line, $pos);
                } else {
                    // Break at the found point
                    $lines_out[] = substr($line, 0, $pos);
                    // Move along by the amount we dealt with
                    $line = substr($line, $pos + 1);
                }
                // If processing headers add a LWSP-char to the front of new line RFC822 section 3.1.1
                if ($in_headers) {
                    $line = "\t".$line;
                }
            }
            $lines_out[] = $line;
            // Send the lines to the server
            foreach ($lines_out as $line_out) {
                // RFC2821 section 4.5.2
                if (!empty($line_out) and $line_out[0] == '.') {
                    $line_out = '.'.$line_out;
                }
                $this->client_send($line_out.self::crlf);
            }
        }
        // Message data has been sent, complete the command
        // Increase timelimit for end of DATA command
        $savetimelimit = $this->timelimit;
        $this->timelimit = $this->timelimit * 2;
        $result = $this->send_command('DATA END', '.', 250);
        // Restore timelimit
        $this->timelimit = $savetimelimit;
        return $result;
    }

    /**
     * Send an SMTP HELO or EHLO command.
     * Used to identify the sending server to the receiving server.
     * This makes sure that client and server are in a known state.
     * Implements RFC 821: HELO <SP> <domain> <CRLF>
     * and RFC 2821 EHLO.
     * @param string $host The host name or IP to connect to
     * @access public
     * @return boolean
     */
    public function hello($host = '') {
        // Try extended hello first (RFC 2821)
        return (boolean)($this->send_hello('EHLO', $host) or $this->send_hello('HELO', $host));
    }

    /**
     * Send an SMTP HELO or EHLO command.
     * Low-level implementation used by hello()
     * @see hello()
     * @param string $hello The HELO string
     * @param string $host The hostname to say we are
     * @access protected
     * @return boolean
     */
    protected function send_hello($hello, $host) {
        $noerror = $this->send_command($hello, $hello.' '.$host, 250);
        $this->helo_rply = $this->last_reply;
        if ($noerror) {
            $this->parse_hello_fields($hello);
        } else {
            $this->server_caps = null;
        }
        return $noerror;
    }

    /**
     * Parse a reply to HELO/EHLO command to discover server extensions.
     * In case of HELO, the only parameter that can be discovered is a server name.
     * @access protected
     * @param string $type - 'HELO' or 'EHLO'
     */
    protected function parse_hello_fields($type) {
        $this->server_caps = array();
        $lines = explode("\n", $this->helo_rply);
        foreach ($lines as $n => $s) {
            // First 4 chars contain response code followed by - or space
            $s = trim(substr($s, 4));
            if (empty($s)) {
                continue;
            }
            $fields = explode(' ', $s);
            if (!empty($fields)) {
                if (!$n) {
                    $name = $type;
                    $fields = $fields[0];
                } else {
                    $name = array_shift($fields);
                    switch ($name) {
                        case 'SIZE':
                            $fields = ($fields ? $fields[0] : 0);
                            break;
                        case 'AUTH':
                            if (!is_array($fields)) {
                                $fields = array();
                            }
                            break;
                        default:
                            $fields = true;
                    }
                }
                $this->server_caps[$name] = $fields;
            }
        }
    }

    /**
     * Send an SMTP MAIL command.
     * Starts a mail transaction from the email address specified in
     * $from. Returns true if successful or false otherwise. If True
     * the mail transaction is started and then one or more recipient
     * commands may be called followed by a data command.
     * Implements rfc 821: MAIL <SP> FROM:<reverse-path> <CRLF>
     * @param string $from Source address of this message
     * @access public
     * @return boolean
     */
    public function mail($from) {
        $useVerp = ($this->do_verp ? ' XVERP' : '');
        return $this->send_command('MAIL FROM', 'MAIL FROM:<'.$from.'>'.$useVerp, 250);
    }

    /**
     * Send an SMTP QUIT command.
     * Closes the socket if there is no error or the $close_on_error argument is true.
     * Implements from rfc 821: QUIT <CRLF>
     * @param boolean $close_on_error Should the connection close if an error occurs?
     * @access public
     * @return boolean
     */
    public function quit($close_on_error = true) {
        $noerror = $this->send_command('QUIT', 'QUIT', 221);
        $err = $this->error; // Save any error
        if ($noerror or $close_on_error) {
            $this->close();
            $this->error = $err; // Restore any error from the quit command
        }
        return $noerror;
    }

    /**
     * Send an SMTP RCPT command.
     * Sets the TO argument to $toaddr.
     * Returns true if the recipient was accepted false if it was rejected.
     * Implements from rfc 821: RCPT <SP> TO:<forward-path> <CRLF>
     * @param string $address The address the message is being sent to
     * @access public
     * @return boolean
     */
    public function recipient($address) {
        return $this->send_command('RCPT TO', 'RCPT TO:<'.$address.'>', array(250, 251));
    }

    /**
     * Send an SMTP RSET command.
     * Abort any transaction that is currently in progress.
     * Implements rfc 821: RSET <CRLF>
     * @access public
     * @return boolean True on success.
     */
    public function reset() {
        return $this->send_command('RSET', 'RSET', 250);
    }

    /**
     * Send a command to an SMTP server and check its return code.
     * @param string $command The command name - not sent to the server
     * @param string $commandstring The actual command to send
     * @param integer|array $expect One or more expected integer success codes
     * @access protected
     * @return boolean True on success.
     */
    protected function send_command($command, $commandstring, $expect) {
        if (!$this->connected()) {
            $this->set_error("Called $command without being connected");
            return false;
        }
        // Reject line breaks in all commands
        if (strpos($commandstring, "\n") !== false or strpos($commandstring, "\r") !== false) {
            $this->set_error("Command '$command' contained line breaks");
            return false;
        }
        $this->client_send($commandstring.self::crlf);
        $this->last_reply = $this->get_lines();
        // Fetch SMTP code and possible error code explanation
        $matches = array();
        if (preg_match("/^([0-9]{3})[ -](?:([0-9]\\.[0-9]\\.[0-9]) )?/", $this->last_reply, $matches)) {
            $code = $matches[1];
            $code_ex = (count($matches) > 2 ? $matches[2] : null);
            // Cut off error code from each response line
            $detail = preg_replace(
                "/{$code}[ -]".($code_ex ? str_replace('.', '\\.', $code_ex).' ' : '')."/m", '', $this->last_reply
            );
        } else {
            // Fall back to simple parsing if regex fails
            $code = substr($this->last_reply, 0, 3);
            $code_ex = null;
            $detail = substr($this->last_reply, 4);
        }
        $this->edebug('SERVER -> CLIENT: '.$this->last_reply, self::debug_server);
        if (!in_array($code, (array)$expect)) {
            $this->set_error("$command command failed", $detail, $code, $code_ex);
            $this->edebug('SMTP ERROR: '.$this->error['error'].': '.$this->last_reply, self::debug_client);
            return false;
        }
        $this->set_error('');
        return true;
    }

    /**
     * Send an SMTP SAML command.
     * Starts a mail transaction from the email address specified in $from.
     * Returns true if successful or false otherwise. If True
     * the mail transaction is started and then one or more recipient
     * commands may be called followed by a data command. This command
     * will send the message to the users terminal if they are logged
     * in and send them an email.
     * Implements rfc 821: SAML <SP> FROM:<reverse-path> <CRLF>
     * @param string $from The address the message is from
     * @access public
     * @return boolean
     */
    public function send_and_mail($from) {
        return $this->send_command('SAML', "SAML FROM:$from", 250);
    }

    /**
     * Send an SMTP VRFY command.
     * @param string $name The name to verify
     * @access public
     * @return boolean
     */
    public function verify($name) {
        return $this->send_command('VRFY', "VRFY $name", array(250, 251));
    }

    /**
     * Send an SMTP NOOP command.
     * Used to keep keep-alives alive, doesn't actually do anything
     * @access public
     * @return boolean
     */
    public function noop() {
        return $this->send_command('NOOP', 'NOOP', 250);
    }

    /**
     * Send an SMTP TURN command.
     * This is an optional command for SMTP that this class does not support.
     * This method is here to make the RFC821 Definition complete for this class
     * and _may_ be implemented in future
     * Implements from rfc 821: TURN <CRLF>
     * @access public
     * @return boolean
     */
    public function turn() {
        $this->set_error('The SMTP TURN command is not implemented');
        $this->edebug('SMTP NOTICE: '.$this->error['error'], self::debug_client);
        return false;
    }

    /**
     * Send raw data to the server.
     * @param string $data The data to send
     * @access public
     * @return integer|boolean The number of bytes sent to the server or false on error
     */
    public function client_send($data) {
        $this->edebug("CLIENT -> SERVER: $data", self::debug_client);
        set_error_handler(array($this, 'error_handler'));
        $result = fwrite($this->smtp_conn, $data);
        restore_error_handler();
        return $result;
    }

    /**
     * Get the latest error.
     * @access public
     * @return array
     */
    public function get_error() {
        return $this->error;
    }

    /**
     * Get SMTP extensions available on the server
     * @access public
     * @return array|null
     */
    public function get_server_ext_list() {
        return $this->server_caps;
    }

    /**
     * A multipurpose method
     * The method works in three ways, dependent on argument value and current state
     *   1. HELO/EHLO was not sent - returns null and set up $this->error
     *   2. HELO was sent
     *     $name = 'HELO': returns server name
     *     $name = 'EHLO': returns boolean false
     *     $name = any string: returns null and set up $this->error
     *   3. EHLO was sent
     *     $name = 'HELO'|'EHLO': returns server name
     *     $name = any string: if extension $name exists, returns boolean True
     *       or its options. Otherwise returns boolean False
     * In other words, one can use this method to detect 3 conditions:
     *  - null returned: handshake was not or we don't know about ext (refer to $this->error)
     *  - false returned: the requested feature exactly not exists
     *  - positive value returned: the requested feature exists
     * @param string $name Name of SMTP extension or 'HELO'|'EHLO'
     * @return mixed
     */
    public function get_server_ext($name) {
        if (!$this->server_caps) {
            $this->set_error('No HELO/EHLO was sent');
            return null;
        }
        // the tight logic knot ;)
        if (!array_key_exists($name, $this->server_caps)) {
            if ($name == 'HELO') {
                return $this->server_caps['EHLO'];
            }
            if ($name == 'EHLO' || array_key_exists('EHLO', $this->server_caps)) {
                return false;
            }
            $this->set_error('HELO handshake was used. Client knows nothing about server extensions');
            return null;
        }
        return $this->server_caps[$name];
    }

    /**
     * Get the last reply from the server.
     * @access public
     * @return string
     */
    public function get_last_reply() {
        return $this->last_reply;
    }

    /**
     * Read the SMTP server's response.
     * Either before eof or socket timeout occurs on the operation.
     * With SMTP we can tell if we have more lines to read if the
     * 4th character is '-' symbol. If it is a space then we don't
     * need to read anything else.
     * @access protected
     * @return string
     */
    protected function get_lines() {
        // If the connection is bad, give up straight away
        if (!is_resource($this->smtp_conn)) {
            return '';
        }
        $data = '';
        $endtime = 0;
        stream_set_timeout($this->smtp_conn, $this->timeout);
        if ($this->timelimit > 0) {
            $endtime = time() + $this->timelimit;
        }
        while (is_resource($this->smtp_conn) && !feof($this->smtp_conn)) {
            $str = @fgets($this->smtp_conn, 515);
            $this->edebug("SMTP -> get_lines(): \$data is \"$data\"", self::debug_lowlevel);
            $this->edebug("SMTP -> get_lines(): \$str is  \"$str\"", self::debug_lowlevel);
            $data .= $str;
            // If response is only 3 chars (not valid, but RFC5321 S4.2 says it must be handled),
            // or 4th character is a space, we are done reading, break the loop,
            // string array access is a micro-optimisation over strlen
            if (!isset($str[3]) or (isset($str[3]) and $str[3] == ' ')) {
                break;
            }
            // Timed-out? Log and break
            $info = stream_get_meta_data($this->smtp_conn);
            if ($info['timed_out']) {
                $this->edebug('SMTP -> get_lines(): timed-out ('.$this->timeout.' sec)', self::debug_lowlevel);
                break;
            }
            // Now check if reads took too long
            if ($endtime and time() > $endtime) {
                $this->edebug('SMTP -> get_lines(): timelimit reached ('.$this->timelimit.' sec)', self::debug_lowlevel);
                break;
            }
        }
        return $data;
    }

    /**
     * Enable or disable VERP address generation.
     * @param boolean $enabled
     */
    public function set_verp($enabled = false) {
        $this->do_verp = $enabled;
    }

    /**
     * Get VERP address generation mode.
     * @return boolean
     */
    public function get_verp() {
        return $this->do_verp;
    }

    /**
     * Set error messages and codes.
     * @param string $message The error message
     * @param string $detail Further detail on the error
     * @param string $smtp_code An associated SMTP error code
     * @param string $smtp_code_ex Extended SMTP code
     */
    protected function set_error($message, $detail = '', $smtp_code = '', $smtp_code_ex = '') {
        $this->error = array(
            'error' => $message, 'detail' => $detail, 'smtp_code' => $smtp_code, 'smtp_code_ex' => $smtp_code_ex
        );
    }

    /**
     * Set debug output method.
     * @param string|callable $method The name of the mechanism to use for debugging output, or a callable to handle it.
     */
    public function set_debug_output($method = 'echo') {
        $this->debugoutput = $method;
    }

    /**
     * Get debug output method.
     * @return string
     */
    public function get_debug_output() {
        return $this->debugoutput;
    }

    /**
     * Set debug output level.
     * @param integer $level
     */
    public function set_debug_level($level = 0) {
        $this->do_debug = $level;
    }

    /**
     * Get debug output level.
     * @return integer
     */
    public function get_debug_level() {
        return $this->do_debug;
    }

    /**
     * Set SMTP timeout.
     * @param integer $timeout
     */
    public function set_timeout($timeout = 0) {
        $this->timeout = $timeout;
    }

    /**
     * Get SMTP timeout.
     * @return integer
     */
    public function get_timeout() {
        return $this->timeout;
    }

    /**
     * Reports an error number and string.
     * @param integer $errno The error number returned by PHP.
     * @param string $errmsg The error message returned by PHP.
     * @param string $errfile The file the error occurred in
     * @param integer $errline The line number the error occurred on
     */
    protected function error_handler($errno, $errmsg, $errfile = '', $errline = 0) {
        $notice = 'Connection failed.';
        $this->set_error($notice, $errno, $errmsg);
        $this->edebug($notice.' Error #'.$errno.': '.$errmsg." [$errfile line $errline]", self::debug_connection);
    }

    /**
     * Will return the ID of the last smtp transaction based on a list of patterns provided
     * in SMTP::$smtp_transaction_id_patterns.
     * If no reply has been received yet, it will return null.
     * If no pattern has been matched, it will return false.
     * @return bool|null|string
     */
    public function get_last_transaction_id() {
        $reply = $this->get_last_reply();
        if (empty($reply)) {
            return null;
        }
        foreach ($this->smtp_transaction_id_patterns as $smtp_transaction_id_pattern) {
            if (preg_match($smtp_transaction_id_pattern, $reply, $matches)) {
                return $matches[1];
            }
        }
        return false;
    }
}

/**
 * mailer - PHP email creation and transport class.
 */
class mailer {

    /**
     * The PHPMailer Version number.
     * @var string
     */
    public $version = '5.2.23';

    /**
     * Email priority.
     * Options: null (default), 1 = High, 3 = Normal, 5 = low.
     * When null, the header is not set at all.
     * @var integer
     */
    public $priority = null;

    /**
     * The character set of the message.
     * @var string
     */
    public $char_set = 'iso-8859-1';

    /**
     * The MIME Content-type of the message.
     * @var string
     */
    public $content_type = 'text/plain';

    /**
     * The message encoding.
     * Options: "8bit", "7bit", "binary", "base64", and "quoted-printable".
     * @var string
     */
    public $encoding = '8bit';

    /**
     * Holds the most recent mailer error message.
     * @var string
     */
    public $error_info = '';

    /**
     * The From email address for the message.
     * @var string
     */
    public $from = 'root@localhost';

    /**
     * The From name of the message.
     * @var string
     */
    public $from_name = 'Root User';

    /**
     * The Sender email (Return-Path) of the message.
     * If not empty, will be sent via -f to sendmail or as 'MAIL FROM' in smtp mode.
     * @var string
     */
    public $sender = '';

    /**
     * The Return-Path of the message.
     * If empty, it will be set to either From or Sender.
     * @var string
     * @deprecated Email senders should never set a return-path header;
     * it's the receiver's job (RFC5321 section 4.4), so this no longer does anything.
     * @link https://tools.ietf.org/html/rfc5321#section-4.4 RFC5321 reference
     */
    public $return_path = '';

    /**
     * The Subject of the message.
     * @var string
     */
    public $subject = '';

    /**
     * An HTML or plain text message body.
     * If HTML then call isHTML(true).
     * @var string
     */
    public $body = '';

    /**
     * The plain-text message body.
     * This body can be read by mail clients that do not have HTML email
     * capability such as mutt & Eudora.
     * Clients that can read HTML will view the normal Body.
     * @var string
     */
    public $alt_body = '';

    /**
     * An iCal message part body.
     * Only supported in simple alt or alt_inline message types
     * To generate iCal events, use the bundled extras/EasyPeasyICS.php class or iCalcreator
     * @link http://sprain.ch/blog/downloads/php-class-easypeasyics-create-ical-files-with-php/
     * @link http://kigkonsult.se/iCalcreator/
     * @var string
     */
    public $ical = '';

    /**
     * The complete compiled MIME message body.
     * @access protected
     * @var string
     */
    protected $mime_body = '';

    /**
     * The complete compiled MIME message headers.
     * @var string
     * @access protected
     */
    protected $mime_header = '';

    /**
     * Extra headers that createHeader() doesn't fold in.
     * @var string
     * @access protected
     */
    protected $mail_header = '';

    /**
     * Word-wrap the message body to this number of chars.
     * Set to 0 to not wrap. A useful value here is 78, for RFC2822 section 2.1.1 compliance.
     * @var integer
     */
    public $word_wrap = 0;

    /**
     * Which method to use to send mail.
     * Options: "mail", "sendmail", or "smtp".
     * @var string
     */
    public $mailer = 'mail';

    /**
     * The path to the sendmail program.
     * @var string
     */
    public $sendmail = '/usr/sbin/sendmail';

    /**
     * Whether mail() uses a fully sendmail-compatible MTA.
     * One which supports sendmail's "-oi -f" options.
     * @var boolean
     */
    public $use_sendmail_options = true;

    /**
     * Path to PHPMailer plugins.
     * Useful if the SMTP class is not in the PHP include path.
     * @var string
     * @deprecated Should not be needed now there is an autoloader.
     */
    public $plugin_dir = '';

    /**
     * The email address that a reading confirmation should be sent to, also known as read receipt.
     * @var string
     */
    public $confirm_reading_to = '';

    /**
     * The hostname to use in the Message-ID header and as default HELO string.
     * If empty, PHPMailer attempts to find one with, in order,
     * $_SERVER['SERVER_NAME'], gethostname(), php_uname('n'), or the value
     * 'localhost.localdomain'.
     * @var string
     */
    public $hostname = '';

    /**
     * An ID to be used in the Message-ID header.
     * If empty, a unique id will be generated.
     * You can set your own, but it must be in the format "<id@domain>",
     * as defined in RFC5322 section 3.6.4 or it will be ignored.
     * @see https://tools.ietf.org/html/rfc5322#section-3.6.4
     * @var string
     */
    public $message_id = '';

    /**
     * The message Date to be used in the Date header.
     * If empty, the current date will be added.
     * @var string
     */
    public $message_date = '';

    /**
     * SMTP hosts.
     * Either a single hostname or multiple semicolon-delimited hostnames.
     * You can also specify a different port
     * for each host by using this format: [hostname:port]
     * (e.g. "smtp1.example.com:25;smtp2.example.com").
     * You can also specify encryption type, for example:
     * (e.g. "tls://smtp1.example.com:587;ssl://smtp2.example.com:465").
     * Hosts will be tried in order.
     * @var string
     */
    public $host = 'localhost';

    /**
     * The default SMTP server port.
     * @var integer
     */
    public $port = 25;

    /**
     * The SMTP HELO of the message.
     * Default is $Hostname. If $Hostname is empty, PHPMailer attempts to find
     * one with the same method described above for $Hostname.
     * @var string
     * @see mailer::$hostname
     */
    public $helo = '';

    /**
     * What kind of encryption to use on the SMTP connection.
     * Options: '', 'ssl' or 'tls'
     * @var string
     */
    public $smtp_secure = '';

    /**
     * Whether to enable TLS encryption automatically if a server supports it,
     * even if `SMTPSecure` is not set to 'tls'.
     * Be aware that in PHP >= 5.6 this requires that the server's certificates are valid.
     * @var boolean
     */
    public $smtp_auto_tls = true;

    /**
     * Whether to use SMTP authentication.
     * Uses the Username and Password properties.
     * @var boolean
     * @see mailer::$username
     * @see mailer::$password
     */
    public $smtp_auth = false;

    /**
     * Options array passed to stream_context_create when connecting via SMTP.
     * @var array
     */
    public $smtp_options = array();

    /**
     * SMTP username.
     * @var string
     */
    public $username = '';

    /**
     * SMTP password.
     * @var string
     */
    public $password = '';

    /**
     * SMTP auth type.
     * Options are CRAM-MD5, LOGIN, PLAIN attempted in that order if not specified
     * @var string
     */
    public $auth_type = '';

    /**
     * The SMTP server timeout in seconds.
     * Default of 5 minutes (300sec) is from RFC2821 section 4.5.3.2
     * @var integer
     */
    public $timeout = 300;

    /**
     * SMTP class debug output mode.
     * Debug output level.
     * Options:
     * * `0` No output
     * * `1` Commands
     * * `2` Data and commands
     * * `3` As 2 plus connection status
     * * `4` Low-level data output
     * @var integer
     * @see smtp::$do_debug
     */
    public $smtp_debug = 0;

    /**
     * How to handle debug output.
     * Options:
     * * `echo` Output plain-text as-is, appropriate for CLI
     * * `html` Output escaped, line breaks converted to `<br>`, appropriate for browser output
     * * `error_log` Output to error log as configured in php.ini
     *
     * Alternatively, you can provide a callable expecting two params: a message string and the debug level:
     * <code>
     * $mail->Debugoutput = function($str, $level) {echo "debug level $level; message: $str";};
     * </code>
     * @var string|callable
     * @see smtp::$debugoutput
     */
    public $debugoutput = 'echo';

    /**
     * Whether to keep SMTP connection open after each message.
     * If this is set to true then to close the connection
     * requires an explicit call to smtpClose().
     * @var boolean
     */
    public $smtp_keep_alive = false;

    /**
     * Whether to split multiple to addresses into multiple messages
     * or send them all in one message.
     * Only supported in `mail` and `sendmail` transports, not in SMTP.
     * @var boolean
     */
    public $single_to = false;

    /**
     * Storage for addresses when SingleTo is enabled.
     * @var array
     */
    public $single_to_array = array();

    /**
     * Whether to generate VERP addresses on send.
     * Only applicable when sending via SMTP.
     * @link https://en.wikipedia.org/wiki/Variable_envelope_return_path
     * @link http://www.postfix.org/VERP_README.html Postfix VERP info
     * @var boolean
     */
    public $do_verp = false;

    /**
     * Whether to allow sending messages with an empty body.
     * @var boolean
     */
    public $allow_empty = false;

    /**
     * The default line ending.
     * @note The default remains "\n". We force CRLF where we know
     *        it must be used via self::CRLF.
     * @var string
     */
    public $le = "\n";

    /**
     * DKIM selector.
     * @var string
     */
    public $dkim_selector = '';

    /**
     * DKIM Identity.
     * Usually the email address used as the source of the email.
     * @var string
     */
    public $dkim_identity = '';

    /**
     * DKIM passphrase.
     * Used if your key is encrypted.
     * @var string
     */
    public $dkim_passphrase = '';

    /**
     * DKIM signing domain name.
     * @example 'example.com'
     * @var string
     */
    public $dkim_domain = '';

    /**
     * DKIM private key file path.
     * @var string
     */
    public $dkim_private = '';

    /**
     * DKIM private key string.
     * If set, takes precedence over `$DKIM_private`.
     * @var string
     */
    public $dkim_private_string = '';

    /**
     * Callback Action function name.
     *
     * The function that handles the result of the send email action.
     * It is called out by send() for each email sent.
     *
     * Value can be any php callable: http://www.php.net/is_callable
     *
     * Parameters:
     *   boolean $result        result of the send action
     *   string  $to            email address of the recipient
     *   string  $cc            cc email addresses
     *   string  $bcc           bcc email addresses
     *   string  $subject       the subject
     *   string  $body          the email body
     *   string  $from          email address of sender
     * @var string
     */
    public $action_function = '';

    /**
     * What to put in the X-Mailer header.
     * Options: An empty string for PHPMailer default, whitespace for none, or a string to use
     * @var string
     */
    public $xmailer = '';

    /**
     * Which validator to use by default when validating email addresses.
     * May be a callable to inject your own validator, but there are several built-in validators.
     * @see mailer::validate_address()
     * @var string|callable
     * @static
     */
    public static $validator = 'auto';

    /**
     * An instance of the SMTP sender class.
     * @var smtp
     * @access protected
     */
    protected $smtp = null;

    /**
     * The array of 'to' names and addresses.
     * @var array
     * @access protected
     */
    protected $to = array();

    /**
     * The array of 'cc' names and addresses.
     * @var array
     * @access protected
     */
    protected $cc = array();

    /**
     * The array of 'bcc' names and addresses.
     * @var array
     * @access protected
     */
    protected $bcc = array();

    /**
     * The array of reply-to names and addresses.
     * @var array
     * @access protected
     */
    protected $reply_to = array();

    /**
     * An array of all kinds of addresses.
     * Includes all of $to, $cc, $bcc
     * @var array
     * @access protected
     * @see mailer::$to @see PHPMailer::$cc @see PHPMailer::$bcc
     */
    protected $all_recipients = array();

    /**
     * An array of names and addresses queued for validation.
     * In send(), valid and non duplicate entries are moved to $all_recipients
     * and one of $to, $cc, or $bcc.
     * This array is used only for addresses with IDN.
     * @var array
     * @access protected
     * @see mailer::$to @see PHPMailer::$cc @see PHPMailer::$bcc
     * @see mailer::$all_recipients
     */
    protected $recipients_queue = array();

    /**
     * An array of reply-to names and addresses queued for validation.
     * In send(), valid and non duplicate entries are moved to $ReplyTo.
     * This array is used only for addresses with IDN.
     * @var array
     * @access protected
     * @see mailer::$reply_to
     */
    protected $reply_to_queue = array();

    /**
     * The array of attachments.
     * @var array
     * @access protected
     */
    protected $attachment = array();

    /**
     * The array of custom headers.
     * @var array
     * @access protected
     */
    protected $custom_header = array();

    /**
     * The most recent Message-ID (including angular brackets).
     * @var string
     * @access protected
     */
    protected $last_message_id = '';

    /**
     * The message's MIME type.
     * @var string
     * @access protected
     */
    protected $message_type = '';

    /**
     * The array of MIME boundary strings.
     * @var array
     * @access protected
     */
    protected $boundary = array();

    /**
     * The array of available languages.
     * @var array
     * @access protected
     */
    protected $language = array();

    /**
     * The number of errors encountered.
     * @var integer
     * @access protected
     */
    protected $error_count = 0;

    /**
     * The S/MIME certificate file path.
     * @var string
     * @access protected
     */
    protected $sign_cert_file = '';

    /**
     * The S/MIME key file path.
     * @var string
     * @access protected
     */
    protected $sign_key_file = '';

    /**
     * The optional S/MIME extra certificates ("CA Chain") file path.
     * @var string
     * @access protected
     */
    protected $sign_extracerts_file = '';

    /**
     * The S/MIME password for the key.
     * Used only if the key is encrypted.
     * @var string
     * @access protected
     */
    protected $sign_key_pass = '';

    /**
     * Whether to throw exceptions for errors.
     * @var boolean
     * @access protected
     */
    protected $exceptions = false;

    /**
     * Unique ID used for message ID and boundaries.
     * @var string
     * @access protected
     */
    protected $uniqueid = '';

    /**
     * Error severity: message only, continue processing.
     */
    const stop_message = 0;

    /**
     * Error severity: message, likely ok to continue processing.
     */
    const stop_continue = 1;

    /**
     * Error severity: message, plus full stop, critical error reached.
     */
    const stop_critical = 2;

    /**
     * SMTP RFC standard line ending.
     */
    const crlf = "\r\n";

    /**
     * The maximum line length allowed by RFC 2822 section 2.1.1
     * @var integer
     */
    const max_line_length = 998;

    /**
     * Constructor.
     * @param boolean $exceptions Should we throw external exceptions?
     */
    public function __construct($exceptions = null) {
        if ($exceptions !== null) {
            $this->exceptions = (boolean)$exceptions;
        }
    }

    /**
     * Destructor.
     */
    public function __destruct() {
        // Close any open SMTP connection nicely
        $this->smtp_close();
    }

    /**
     * Call mail() in a safe_mode-aware fashion.
     * Also, unless sendmail_path points to sendmail (or something that
     * claims to be sendmail), don't pass params (not a perfect fix,
     * but it will do)
     * @param string $to To
     * @param string $subject Subject
     * @param string $body Message Body
     * @param string $header Additional Header(s)
     * @param string $params Params
     * @access private
     * @return boolean
     */
    private function mail_passthru($to, $subject, $body, $header, $params) {
        // Check overloading of mail function to avoid double-encoding
        if (ini_get('mbstring.func_overload') & 1) {
            $subject = $this->secure_header($subject);
        } else {
            $subject = $this->encode_header($this->secure_header($subject));
        }
        // Can't use additional_parameters in safe_mode, calling mail() with null params breaks
        // @link http://php.net/manual/en/function.mail.php
        if (ini_get('safe_mode') or !$this->use_sendmail_options or is_null($params)) {
            $result = @mail($to, $subject, $body, $header);
        } else {
            $result = @mail($to, $subject, $body, $header, $params);
        }
        return $result;
    }
    /**
     * Output debugging info via user-defined method.
     * Only generates output if SMTP debug output is enabled (@see smtp::$do_debug).
     * @see mailer::$debugoutput
     * @see mailer::$smtp_debug
     * @param string $str
     */
    protected function edebug($str) {
        if ($this->smtp_debug <= 0) {
            return;
        }
        // Avoid clash with built-in function names
        if (!in_array($this->debugoutput, array('error_log', 'html', 'echo')) and is_callable($this->debugoutput)) {
            call_user_func($this->debugoutput, $str, $this->smtp_debug);
            return;
        }
        switch ($this->debugoutput) {
            case 'error_log':
                // Don't output, just log
                error_log($str);
                break;
            case 'html':
                // Cleans up output a bit for a better looking, HTML-safe output
                echo htmlentities(preg_replace('/[\r\n]+/', '', $str), ENT_QUOTES, 'UTF-8')."<br>\n";
                break;
            case 'echo':
            default:
                //Normalize line breaks
                $str = preg_replace('/\r\n?/ms', "\n", $str);
                echo gmdate('Y-m-d H:i:s')."\t".str_replace(
                    "\n", "\n                   \t                  ", trim($str)
                )."\n";
        }
    }

    /**
     * Sets message type to HTML or plain.
     * @param boolean $isHtml True for HTML mode.
     * @return void
     */
    public function is_html($isHtml = true) {
        if ($isHtml) {
            $this->content_type = 'text/html';
        } else {
            $this->content_type = 'text/plain';
        }
    }

    /**
     * Send messages using SMTP.
     * @return void
     */
    public function is_smtp() {
        $this->mailer = 'smtp';
    }

    /**
     * Send messages using PHP's mail() function.
     * @return void
     */
    public function is_mail() {
        $this->mailer = 'mail';
    }

    /**
     * Send messages using $Sendmail.
     * @return void
     */
    public function is_sendmail() {
        $ini_sendmail_path = ini_get('sendmail_path');
        if (!stristr($ini_sendmail_path, 'sendmail')) {
            $this->sendmail = '/usr/sbin/sendmail';
        } else {
            $this->sendmail = $ini_sendmail_path;
        }
        $this->mailer = 'sendmail';
    }

    /**
     * Send messages using qmail.
     * @return void
     */
    public function is_qmail() {
        $ini_sendmail_path = ini_get('sendmail_path');
        if (!stristr($ini_sendmail_path, 'qmail')) {
            $this->sendmail = '/var/qmail/bin/qmail-inject';
        } else {
            $this->sendmail = $ini_sendmail_path;
        }
        $this->mailer = 'qmail';
    }

    /**
     * Add a "To" address.
     * @param string $address The email address to send to
     * @param string $name
     * @return boolean true on success, false if address already used or invalid in some way
     */
    public function add_address($address, $name = '') {
        return $this->add_or_enqueue_an_address('to', $address, $name);
    }

    /**
     * Add a "CC" address.
     * @note: This function works with the SMTP mailer on win32, not with the "mail" mailer.
     * @param string $address The email address to send to
     * @param string $name
     * @return boolean true on success, false if address already used or invalid in some way
     */
    public function add_cc($address, $name = '') {
        return $this->add_or_enqueue_an_address('cc', $address, $name);
    }

    /**
     * Add a "BCC" address.
     * @note: This function works with the SMTP mailer on win32, not with the "mail" mailer.
     * @param string $address The email address to send to
     * @param string $name
     * @return boolean true on success, false if address already used or invalid in some way
     */
    public function add_bcc($address, $name = '') {
        return $this->add_or_enqueue_an_address('bcc', $address, $name);
    }

    /**
     * Add a "Reply-To" address.
     * @param string $address The email address to reply to
     * @param string $name
     * @return boolean true on success, false if address already used or invalid in some way
     */
    public function add_reply_to($address, $name = '') {
        return $this->add_or_enqueue_an_address('Reply-To', $address, $name);
    }

    /**
     * Add an address to one of the recipient arrays or to the ReplyTo array. Because PHPMailer
     * can't validate addresses with an IDN without knowing the PHPMailer::$CharSet (that can still
     * be modified after calling this function), addition of such addresses is delayed until send().
     * Addresses that have been added already return false, but do not throw exceptions.
     * @param string $kind One of 'to', 'cc', 'bcc', or 'ReplyTo'
     * @param string $address The email address to send, resp. to reply to
     * @param string $name
     * @throws \Exception
     * @return boolean true on success, false if address already used or invalid in some way
     * @access protected
     */
    protected function add_or_enqueue_an_address($kind, $address, $name) {
        $address = trim($address);
        $name = trim(preg_replace('/[\r\n]+/', '', $name)); //Strip breaks and trim
        if (($pos = strrpos($address, '@')) === false) {
            // At-sign is misssing.
            $error_message = $this->lang('invalid_address')." (addAnAddress $kind): $address";
            $this->set_error($error_message);
            $this->edebug($error_message);
            if ($this->exceptions) {
                throw new \Exception($error_message);
            }
            return false;
        }
        $params = array($kind, $address, $name);
        // Enqueue addresses with IDN until we know the PHPMailer::$CharSet.
        if ($this->has8bit_chars(substr($address, ++$pos)) and $this->idn_supported()) {
            if ($kind != 'Reply-To') {
                if (!array_key_exists($address, $this->recipients_queue)) {
                    $this->recipients_queue[$address] = $params;
                    return true;
                }
            } else {
                if (!array_key_exists($address, $this->reply_to_queue)) {
                    $this->reply_to_queue[$address] = $params;
                    return true;
                }
            }
            return false;
        }
        // Immediately add standard addresses without IDN.
        return call_user_func_array(array($this, 'add_an_address'), $params);
    }

    /**
     * Add an address to one of the recipient arrays or to the ReplyTo array.
     * Addresses that have been added already return false, but do not throw exceptions.
     * @param string $kind One of 'to', 'cc', 'bcc', or 'ReplyTo'
     * @param string $address The email address to send, resp. to reply to
     * @param string $name
     * @throws \Exception
     * @return boolean true on success, false if address already used or invalid in some way
     * @access protected
     */
    protected function add_an_address($kind, $address, $name = '') {
        if (!in_array($kind, array('to', 'cc', 'bcc', 'Reply-To'))) {
            $error_message = $this->lang('Invalid recipient kind: ').$kind;
            $this->set_error($error_message);
            $this->edebug($error_message);
            if ($this->exceptions) {
                throw new \Exception($error_message);
            }
            return false;
        }
        if (!$this->validate_address($address)) {
            $error_message = $this->lang('invalid_address')." (addAnAddress $kind): $address";
            $this->set_error($error_message);
            $this->edebug($error_message);
            if ($this->exceptions) {
                throw new \Exception($error_message);
            }
            return false;
        }
        if ($kind != 'Reply-To') {
            if (!array_key_exists(strtolower($address), $this->all_recipients)) {
                array_push($this->$kind, array($address, $name));
                $this->all_recipients[strtolower($address)] = true;
                return true;
            }
        } else {
            if (!array_key_exists(strtolower($address), $this->reply_to)) {
                $this->reply_to[strtolower($address)] = array($address, $name);
                return true;
            }
        }
        return false;
    }

    /**
     * Parse and validate a string containing one or more RFC822-style comma-separated email addresses
     * of the form "display name <address>" into an array of name/address pairs.
     * Uses the imap_rfc822_parse_adrlist function if the IMAP extension is available.
     * Note that quotes in the name part are removed.
     * @param string $addrstr The address list string
     * @param bool $useimap Whether to use the IMAP extension to parse the list
     * @return array
     * @link http://www.andrew.cmu.edu/user/agreen1/testing/mrbs/web/Mail/RFC822.php A more careful implementation
     */
    public function parse_addresses($addrstr, $useimap = true) {
        $addresses = array();
        if ($useimap and function_exists('imap_rfc822_parse_adrlist')) {
            // Use this built-in parser if it's available
            $list = imap_rfc822_parse_adrlist($addrstr, '');
            foreach ($list as $address) {
                if ($address->host != '.SYNTAX-ERROR.') {
                    if ($this->validate_address($address->mailbox.'@'.$address->host)) {
                        $addresses[] = array(
                            'name' => (property_exists($address, 'personal') ? $address->personal : ''),
                            'address' => $address->mailbox.'@'.$address->host
                        );
                    }
                }
            }
        } else {
            // Use this simpler parser
            $list = explode(',', $addrstr);
            foreach ($list as $address) {
                $address = trim($address);
                // Is there a separate name part?
                if (strpos($address, '<') === false) {
                    // No separate name, just use the whole thing
                    if ($this->validate_address($address)) {
                        $addresses[] = array('name' => '', 'address' => $address);
                    }
                } else {
                    list($name, $email) = explode('<', $address);
                    $email = trim(str_replace('>', '', $email));
                    if ($this->validate_address($email)) {
                        $addresses[] = array(
                            'name' => trim(str_replace(array('"', "'"), '', $name)),
                            'address' => $email
                        );
                    }
                }
            }
        }
        return $addresses;
    }

    /**
     * Set the From and FromName properties.
     * @param string $address
     * @param string $name
     * @param boolean $auto Whether to also set the Sender address, defaults to true
     * @throws \Exception
     * @return boolean
     */
    public function set_from($address, $name = '', $auto = true) {
        $address = trim($address);
        $name = trim(preg_replace('/[\r\n]+/', '', $name)); //Strip breaks and trim
        // Don't validate now addresses with IDN. Will be done in send().
        if (($pos = strrpos($address, '@')) === false or
            (!$this->has8bit_chars(substr($address, ++$pos)) or !$this->idn_supported()) and
            !$this->validate_address($address)) {
            $error_message = $this->lang('invalid_address')." (setFrom) $address";
            $this->set_error($error_message);
            $this->edebug($error_message);
            if ($this->exceptions) {
                throw new \Exception($error_message);
            }
            return false;
        }
        $this->from = $address;
        $this->from_name = $name;
        if ($auto) {
            if (empty($this->sender)) {
                $this->sender = $address;
            }
        }
        return true;
    }

    /**
     * Return the Message-ID header of the last email.
     * Technically this is the value from the last time the headers were created,
     * but it's also the message ID of the last sent message except in
     * pathological cases.
     * @return string
     */
    public function get_last_message_id() {
        return $this->last_message_id;
    }

    /**
     * Check that a string looks like an email address.
     * @param string $address The email address to check
     * @param string|callable $patternselect A selector for the validation pattern to use :
     * * `auto` Pick best pattern automatically;
     * * `pcre8` Use the squiloople.com pattern, requires PCRE > 8.0, PHP >= 5.3.2, 5.2.14;
     * * `pcre` Use old PCRE implementation;
     * * `php` Use PHP built-in FILTER_VALIDATE_EMAIL;
     * * `html5` Use the pattern given by the HTML5 spec for 'email' type form input elements.
     * * `noregex` Don't use a regex: super fast, really dumb.
     * Alternatively you may pass in a callable to inject your own validator, for example:
     * PHPMailer::validateAddress('user@example.com', function($address) {
     *     return (strpos($address, '@') !== false);
     * });
     * You can also set the PHPMailer::$validator static to a callable, allowing built-in methods to use your validator.
     * @return boolean
     * @static
     * @access public
     */
    public static function validate_address($address, $patternselect = null) {
        if (is_null($patternselect)) {
            $patternselect = self::$validator;
        }
        if (is_callable($patternselect)) {
            return call_user_func($patternselect, $address);
        }
        // Reject line breaks in addresses; it's valid RFC5322, but not RFC5321
        if (strpos($address, "\n") !== false or strpos($address, "\r") !== false) {
            return false;
        }
        if (!$patternselect or $patternselect == 'auto') {
            // Check this constant first so it works when extension_loaded() is disabled by safe mode
            // Constant was added in PHP 5.2.4
            if (defined('PCRE_VERSION')) {
                // This pattern can get stuck in a recursive loop in PCRE <= 8.0.2
                if (version_compare(PCRE_VERSION, '8.0.3') >= 0) {
                    $patternselect = 'pcre8';
                } else {
                    $patternselect = 'pcre';
                }
            } elseif (function_exists('extension_loaded') and extension_loaded('pcre')) {
                // Fall back to older PCRE
                $patternselect = 'pcre';
            } else {
                //Filter_var appeared in PHP 5.2.0 and does not require the PCRE extension
                if (version_compare(PHP_VERSION, '5.2.0') >= 0) {
                    $patternselect = 'php';
                } else {
                    $patternselect = 'noregex';
                }
            }
        }
        switch ($patternselect) {
            case 'pcre8':
                /**
                 * Uses the same RFC5322 regex on which FILTER_VALIDATE_EMAIL is based, but allows dotless domains.
                 * @link http://squiloople.com/2009/12/20/email-address-validation/
                 * @copyright 2009-2010 Michael Rushton
                 * Feel free to use and redistribute this code. But please keep this copyright notice.
                 */
                return (boolean)preg_match(
                    '/^(?!(?>(?1)"?(?>\\\[ -~]|[^"])"?(?1)){255,})(?!(?>(?1)"?(?>\\\[ -~]|[^"])"?(?1)){65,}@)' .
                    '((?>(?>(?>((?>(?>(?>\x0D\x0A)?[\t ])+|(?>[\t ]*\x0D\x0A)?[\t ]+)?)(\((?>(?2)' .
                    '(?>[\x01-\x08\x0B\x0C\x0E-\'*-\[\]-\x7F]|\\\[\x00-\x7F]|(?3)))*(?2)\)))+(?2))|(?2))?)' .
                    '([!#-\'*+\/-9=?^-~-]+|"(?>(?2)(?>[\x01-\x08\x0B\x0C\x0E-!#-\[\]-\x7F]|\\\[\x00-\x7F]))*' .
                    '(?2)")(?>(?1)\.(?1)(?4))*(?1)@(?!(?1)[a-z0-9-]{64,})(?1)(?>([a-z0-9](?>[a-z0-9-]*[a-z0-9])?)' .
                    '(?>(?1)\.(?!(?1)[a-z0-9-]{64,})(?1)(?5)){0,126}|\[(?:(?>IPv6:(?>([a-f0-9]{1,4})(?>:(?6)){7}' .
                    '|(?!(?:.*[a-f0-9][:\]]){8,})((?6)(?>:(?6)){0,6})?::(?7)?))|(?>(?>IPv6:(?>(?6)(?>:(?6)){5}:' .
                    '|(?!(?:.*[a-f0-9]:){6,})(?8)?::(?>((?6)(?>:(?6)){0,4}):)?))?(25[0-5]|2[0-4][0-9]|1[0-9]{2}' .
                    '|[1-9]?[0-9])(?>\.(?9)){3}))\])(?1)$/isD',
                    $address
                );
            case 'pcre':
                //An older regex that doesn't need a recent PCRE
                return (boolean)preg_match(
                    '/^(?!(?>"?(?>\\\[ -~]|[^"])"?){255,})(?!(?>"?(?>\\\[ -~]|[^"])"?){65,}@)(?>' .
                    '[!#-\'*+\/-9=?^-~-]+|"(?>(?>[\x01-\x08\x0B\x0C\x0E-!#-\[\]-\x7F]|\\\[\x00-\xFF]))*")' .
                    '(?>\.(?>[!#-\'*+\/-9=?^-~-]+|"(?>(?>[\x01-\x08\x0B\x0C\x0E-!#-\[\]-\x7F]|\\\[\x00-\xFF]))*"))*' .
                    '@(?>(?![a-z0-9-]{64,})(?>[a-z0-9](?>[a-z0-9-]*[a-z0-9])?)(?>\.(?![a-z0-9-]{64,})' .
                    '(?>[a-z0-9](?>[a-z0-9-]*[a-z0-9])?)){0,126}|\[(?:(?>IPv6:(?>(?>[a-f0-9]{1,4})(?>:' .
                    '[a-f0-9]{1,4}){7}|(?!(?:.*[a-f0-9][:\]]){8,})(?>[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){0,6})?' .
                    '::(?>[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){0,6})?))|(?>(?>IPv6:(?>[a-f0-9]{1,4}(?>:' .
                    '[a-f0-9]{1,4}){5}:|(?!(?:.*[a-f0-9]:){6,})(?>[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){0,4})?' .
                    '::(?>(?:[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){0,4}):)?))?(?>25[0-5]|2[0-4][0-9]|1[0-9]{2}' .
                    '|[1-9]?[0-9])(?>\.(?>25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])){3}))\])$/isD',
                    $address
                );
            case 'html5':
                /**
                 * This is the pattern used in the HTML5 spec for validation of 'email' type form input elements.
                 * @link http://www.whatwg.org/specs/web-apps/current-work/#e-mail-state-(type=email)
                 */
                return (boolean)preg_match(
                    '/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}' .
                    '[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/sD',
                    $address
                );
            case 'noregex':
                //No PCRE! Do something _very_ approximate!
                //Check the address is 3 chars or longer and contains an @ that's not the first or last char
                return (strlen($address) >= 3
                    and strpos($address, '@') >= 1
                    and strpos($address, '@') != strlen($address) - 1);
            case 'php':
            default:
                return (boolean)filter_var($address, FILTER_VALIDATE_EMAIL);
        }
    }

    /**
     * Tells whether IDNs (Internationalized Domain Names) are supported or not. This requires the
     * "intl" and "mbstring" PHP extensions.
     * @return bool "true" if required functions for IDN support are present
     */
    public function idn_supported() {
        return function_exists('idn_to_ascii') and function_exists('mb_convert_encoding');
    }

    /**
     * Converts IDN in given email address to its ASCII form, also known as punycode, if possible.
     * Important: Address must be passed in same encoding as currently set in PHPMailer::$CharSet.
     * This function silently returns unmodified address if:
     * - No conversion is necessary (i.e. domain name is not an IDN, or is already in ASCII form)
     * - Conversion to punycode is impossible (e.g. required PHP functions are not available)
     *   or fails for any reason (e.g. domain has characters not allowed in an IDN)
     * @see mailer::$char_set
     * @param string $address The email address to convert
     * @return string The encoded address in ASCII form
     */
    public function punyencode_address($address) {
        // Verify we have required functions, CharSet, and at-sign.
        if ($this->idn_supported() and
            !empty($this->char_set) and
            ($pos = strrpos($address, '@')) !== false) {
            $domain = substr($address, ++$pos);
            // Verify CharSet string is a valid one, and domain properly encoded in this CharSet.
            if ($this->has8bit_chars($domain) and @mb_check_encoding($domain, $this->char_set)) {
                $domain = mb_convert_encoding($domain, 'UTF-8', $this->char_set);
                if (($punycode = defined('INTL_IDNA_VARIANT_UTS46') ?
                    idn_to_ascii($domain, 0, INTL_IDNA_VARIANT_UTS46) :
                    idn_to_ascii($domain)) !== false) {
                    return substr($address, 0, $pos).$punycode;
                }
            }
        }
        return $address;
    }

    /**
     * Create a message and send it.
     * Uses the sending method specified by $Mailer.
     * @throws \Exception
     * @return boolean false on error - See the ErrorInfo property for details of the error.
     */
    public function send() {
        try {
            if (!$this->pre_send()) {
                return false;
            }
            return $this->post_send();
        } catch (\Exception $exc) {
            $this->mail_header = '';
            $this->set_error($exc->getMessage());
            if ($this->exceptions) {
                throw $exc;
            }
            return false;
        }
    }

    /**
     * Prepare a message for sending.
     * @throws \Exception
     * @return boolean
     */
    public function pre_send() {
        try {
            $this->error_count = 0; // Reset errors
            $this->mail_header = '';
            // Dequeue recipient and Reply-To addresses with IDN
            foreach (array_merge($this->recipients_queue, $this->reply_to_queue) as $params) {
                $params[1] = $this->punyencode_address($params[1]);
                call_user_func_array(array($this, 'add_an_address'), $params);
            }
            if ((count($this->to) + count($this->cc) + count($this->bcc)) < 1) {
                throw new \Exception($this->lang('provide_address'), self::stop_critical);
            }
            // Validate From, Sender, and ConfirmReadingTo addresses
            foreach (array('From', 'Sender', 'ConfirmReadingTo') as $address_kind) {
                $this->$address_kind = trim($this->$address_kind);
                if (empty($this->$address_kind)) {
                    continue;
                }
                $this->$address_kind = $this->punyencode_address($this->$address_kind);
                if (!$this->validate_address($this->$address_kind)) {
                    $error_message = $this->lang('invalid_address').' (punyEncode) '.$this->$address_kind;
                    $this->set_error($error_message);
                    $this->edebug($error_message);
                    if ($this->exceptions) {
                        throw new \Exception($error_message);
                    }
                    return false;
                }
            }
            // Set whether the message is multipart/alternative
            if ($this->alternative_exists()) {
                $this->content_type = 'multipart/alternative';
            }
            $this->set_message_type();
            // Refuse to send an empty message unless we are specifically allowing it
            if (!$this->allow_empty and empty($this->body)) {
                throw new \Exception($this->lang('empty_message'), self::stop_critical);
            }
            // Create body before headers in case body makes changes to headers (e.g. altering transfer encoding)
            $this->mime_header = '';
            $this->mime_body = $this->create_body();
            // CreateBody may have added some headers, so retain them
            $tempheaders = $this->mime_header;
            $this->mime_header = $this->create_header();
            $this->mime_header .= $tempheaders;
            // To capture the complete message when using mail(), create
            // an extra header list which createHeader() doesn't fold in
            if ($this->mailer == 'mail') {
                if (count($this->to) > 0) {
                    $this->mail_header .= $this->addr_append('To', $this->to);
                } else {
                    $this->mail_header .= $this->header_line('To', 'undisclosed-recipients:;');
                }
                $this->mail_header .= $this->header_line(
                    'Subject', $this->encode_header($this->secure_header(trim($this->subject)))
                );
            }

            // Sign with DKIM if enabled
            if (!empty($this->dkim_domain)
                && !empty($this->dkim_selector)
                && (!empty($this->dkim_private_string)
                   || (!empty($this->dkim_private) && file_exists($this->dkim_private))
                )
            ) {
                $header_dkim = $this->dkim_add(
                    $this->mime_header.$this->mail_header,
                    $this->encode_header($this->secure_header($this->subject)),
                    $this->mime_body
                );
                $this->mime_header = rtrim($this->mime_header, "\r\n ").self::crlf.
                    str_replace("\r\n", "\n", $header_dkim).self::crlf;
            }
            return true;
        } catch (\Exception $exc) {
            $this->set_error($exc->getMessage());
            if ($this->exceptions) {
                throw $exc;
            }
            return false;
        }
    }

    /**
     * Actually send a message.
     * Send the email via the selected mechanism
     * @throws \Exception
     * @return boolean
     */
    public function post_send() {
        try {
            // Choose the mailer and send through it
            switch ($this->mailer) {
                case 'sendmail':
                case 'qmail':
                    return $this->sendmail_send($this->mime_header, $this->mime_body);
                case 'smtp':
                    return $this->smtp_send($this->mime_header, $this->mime_body);
                case 'mail':
                    return $this->mail_send($this->mime_header, $this->mime_body);
                default:
                    $sendMethod = $this->mailer.'Send';
                    if (method_exists($this, $sendMethod)) {
                        return $this->$sendMethod($this->mime_header, $this->mime_body);
                    }
                    return $this->mail_send($this->mime_header, $this->mime_body);
            }
        } catch (\Exception $exc) {
            $this->set_error($exc->getMessage());
            $this->edebug($exc->getMessage());
            if ($this->exceptions) {
                throw $exc;
            }
        }
        return false;
    }

    /**
     * Send mail using the $Sendmail program.
     * @param string $header The message headers
     * @param string $body The message body
     * @see mailer::$sendmail
     * @throws \Exception
     * @access protected
     * @return boolean
     */
    protected function sendmail_send($header, $body) {
        // CVE-2016-10033, CVE-2016-10045: Don't pass -f if characters will be escaped.
        if (!empty($this->sender) and self::is_shell_safe($this->sender)) {
            if ($this->mailer == 'qmail') {
                $sendmailFmt = '%s -f%s';
            } else {
                $sendmailFmt = '%s -oi -f%s -t';
            }
        } else {
            if ($this->mailer == 'qmail') {
                $sendmailFmt = '%s';
            } else {
                $sendmailFmt = '%s -oi -t';
            }
        }
        $sendmail = sprintf($sendmailFmt, escapeshellcmd($this->sendmail), $this->sender);
        if ($this->single_to) {
            foreach ($this->single_to_array as $toAddr) {
                if (!@$mail = popen($sendmail, 'w')) {
                    throw new \Exception($this->lang('execute').$this->sendmail, self::stop_critical);
                }
                fputs($mail, 'To: '.$toAddr."\n");
                fputs($mail, $header);
                fputs($mail, $body);
                $result = pclose($mail);
                $this->do_callback(
                    ($result == 0), array($toAddr), $this->cc, $this->bcc, $this->subject, $body, $this->from
                );
                if ($result != 0) {
                    throw new \Exception($this->lang('execute').$this->sendmail, self::stop_critical);
                }
            }
        } else {
            if (!@$mail = popen($sendmail, 'w')) {
                throw new \Exception($this->lang('execute').$this->sendmail, self::stop_critical);
            }
            fputs($mail, $header);
            fputs($mail, $body);
            $result = pclose($mail);
            $this->do_callback(
                ($result == 0), $this->to, $this->cc, $this->bcc, $this->subject, $body, $this->from
            );
            if ($result != 0) {
                throw new \Exception($this->lang('execute').$this->sendmail, self::stop_critical);
            }
        }
        return true;
    }

    /**
     * Fix CVE-2016-10033 and CVE-2016-10045 by disallowing potentially unsafe shell characters.
     *
     * Note that escapeshellarg and escapeshellcmd are inadequate for our purposes, especially on Windows.
     * @param string $string The string to be validated
     * @see https://github.com/PHPMailer/PHPMailer/issues/924 CVE-2016-10045 bug report
     * @access protected
     * @return boolean
     */
    protected static function is_shell_safe($string) {
        // Future-proof
        if (escapeshellcmd($string) !== $string
            or !in_array(escapeshellarg($string), array("'$string'", "\"$string\""))
        ) {
            return false;
        }
        $length = strlen($string);
        for ($i = 0; $i < $length; $i++) {
            $c = $string[$i];
            // All other characters have a special meaning in at least one common shell, including = and +.
            // Full stop (.) has a special meaning in cmd.exe, but its impact should be negligible here.
            // Note that this does permit non-Latin alphanumeric characters based on the current locale.
            if (!ctype_alnum($c) && strpos('@_-.', $c) === false) {
                return false;
            }
        }
        return true;
    }

    /**
     * Send mail using the PHP mail() function.
     * @param string $header The message headers
     * @param string $body The message body
     * @link http://www.php.net/manual/en/book.mail.php
     * @throws \Exception
     * @access protected
     * @return boolean
     */
    protected function mail_send($header, $body) {
        $toArr = array();
        foreach ($this->to as $toaddr) {
            $toArr[] = $this->addr_format($toaddr);
        }
        $to = implode(', ', $toArr);
        $params = null;
        // This sets the SMTP envelope sender which gets turned into a return-path header by the receiver
        if (!empty($this->sender) and $this->validate_address($this->sender)) {
            // CVE-2016-10033, CVE-2016-10045: Don't pass -f if characters will be escaped.
            if (self::is_shell_safe($this->sender)) {
                $params = sprintf('-f%s', $this->sender);
            }
        }
        if (!empty($this->sender) and !ini_get('safe_mode') and $this->validate_address($this->sender)) {
            $old_from = ini_get('sendmail_from');
            ini_set('sendmail_from', $this->sender);
        }
        $result = false;
        if ($this->single_to and count($toArr) > 1) {
            foreach ($toArr as $toAddr) {
                $result = $this->mail_passthru($toAddr, $this->subject, $body, $header, $params);
                $this->do_callback($result, array($toAddr), $this->cc, $this->bcc, $this->subject, $body, $this->from);
            }
        } else {
            $result = $this->mail_passthru($to, $this->subject, $body, $header, $params);
            $this->do_callback($result, $this->to, $this->cc, $this->bcc, $this->subject, $body, $this->from);
        }
        if (isset($old_from)) {
            ini_set('sendmail_from', $old_from);
        }
        if (!$result) {
            throw new \Exception($this->lang('instantiate'), self::stop_critical);
        }
        return true;
    }

    /**
     * Get an instance to use for SMTP operations.
     * Override this function to load your own SMTP implementation
     * @return smtp
     */
    public function get_smtp_instance() {
        if (!is_object($this->smtp)) {
            $this->smtp = new smtp;
        }
        return $this->smtp;
    }

    /**
     * Send mail via SMTP.
     * Returns false if there is a bad MAIL FROM, RCPT, or DATA input.
     * Uses the PHPMailerSMTP class by default.
     * @see mailer::get_smtp_instance() to use a different class.
     * @param string $header The message headers
     * @param string $body The message body
     * @throws \Exception
     * @uses smtp
     * @access protected
     * @return boolean
     */
    protected function smtp_send($header, $body) {
        $bad_rcpt = array();
        if (!$this->smtp_connect($this->smtp_options)) {
            throw new \Exception($this->lang('smtp_connect_failed'), self::stop_critical);
        }
        if (!empty($this->sender) and $this->validate_address($this->sender)) {
            $smtp_from = $this->sender;
        } else {
            $smtp_from = $this->from;
        }
        if (!$this->smtp->mail($smtp_from)) {
            $this->set_error($this->lang('from_failed').$smtp_from.' : '.implode(',', $this->smtp->get_error()));
            throw new \Exception($this->error_info, self::stop_critical);
        }
        // Attempt to send to all recipients
        foreach (array($this->to, $this->cc, $this->bcc) as $togroup) {
            foreach ($togroup as $to) {
                if (!$this->smtp->recipient($to[0])) {
                    $error = $this->smtp->get_error();
                    $bad_rcpt[] = array('to' => $to[0], 'error' => $error['detail']);
                    $isSent = false;
                } else {
                    $isSent = true;
                }
                $this->do_callback($isSent, array($to[0]), array(), array(), $this->subject, $body, $this->from);
            }
        }
        // Only send the DATA command if we have viable recipients
        if ((count($this->all_recipients) > count($bad_rcpt)) and !$this->smtp->data($header.$body)) {
            throw new \Exception($this->lang('data_not_accepted'), self::stop_critical);
        }
        if ($this->smtp_keep_alive) {
            $this->smtp->reset();
        } else {
            $this->smtp->quit();
            $this->smtp->close();
        }
        // Create error message for any bad addresses
        if (count($bad_rcpt) > 0) {
            $errstr = '';
            foreach ($bad_rcpt as $bad) {
                $errstr .= $bad['to'].': '.$bad['error'];
            }
            throw new \Exception(
                $this->lang('recipients_failed').$errstr,
                self::stop_continue
            );
        }
        return true;
    }

    /**
     * Initiate a connection to an SMTP server.
     * Returns false if the operation failed.
     * @param array $options An array of options compatible with stream_context_create()
     * @uses smtp
     * @access public
     * @throws \Exception
     * @return boolean
     */
    public function smtp_connect($options = null) {
        if (is_null($this->smtp)) {
            $this->smtp = $this->get_smtp_instance();
        }
        // If no options are provided, use whatever is set in the instance
        if (is_null($options)) {
            $options = $this->smtp_options;
        }
        // Already connected?
        if ($this->smtp->connected()) {
            return true;
        }
        $this->smtp->set_timeout($this->timeout);
        $this->smtp->set_debug_level($this->smtp_debug);
        $this->smtp->set_debug_output($this->debugoutput);
        $this->smtp->set_verp($this->do_verp);
        $hosts = explode(';', $this->host);
        $lastexception = null;
        foreach ($hosts as $hostentry) {
            $hostinfo = array();
            if (!preg_match('/^((ssl|tls):\/\/)*([a-zA-Z0-9:\[\]\.-]*):?([0-9]*)$/', trim($hostentry), $hostinfo)) {
                // Not a valid host entry
                continue;
            }
            // $hostinfo[2]: optional ssl or tls prefix
            // $hostinfo[3]: the hostname
            // $hostinfo[4]: optional port number
            // The host string prefix can temporarily override the current setting for SMTPSecure
            // If it's not specified, the default value is used
            $prefix = '';
            $secure = $this->smtp_secure;
            $tls = ($this->smtp_secure == 'tls');
            if ('ssl' == $hostinfo[2] or ('' == $hostinfo[2] and 'ssl' == $this->smtp_secure)) {
                $prefix = 'ssl://';
                $tls = false; // Can't have SSL and TLS at the same time
                $secure = 'ssl';
            } elseif ($hostinfo[2] == 'tls') {
                $tls = true;
                // tls doesn't use a prefix
                $secure = 'tls';
            }
            // Do we need the OpenSSL extension?
            $sslext = defined('OPENSSL_ALGO_SHA1');
            if ('tls' === $secure or 'ssl' === $secure) {
                //Check for an OpenSSL constant rather than using extension_loaded, which is sometimes disabled
                if (!$sslext) {
                    throw new \Exception($this->lang('extension_missing').'openssl', self::stop_critical);
                }
            }
            $host = $hostinfo[3];
            $port = $this->port;
            $tport = (integer)$hostinfo[4];
            if ($tport > 0 and $tport < 65536) {
                $port = $tport;
            }
            if ($this->smtp->connect($prefix.$host, $port, $this->timeout, $options)) {
                try {
                    if ($this->helo) {
                        $hello = $this->helo;
                    } else {
                        $hello = $this->server_hostname();
                    }
                    $this->smtp->hello($hello);
                    // Automatically enable TLS encryption if:
                    // * it's not disabled
                    // * we have openssl extension
                    // * we are not already using SSL
                    // * the server offers STARTTLS
                    if ($this->smtp_auto_tls and $sslext and $secure != 'ssl' and $this->smtp->get_server_ext('STARTTLS')) {
                        $tls = true;
                    }
                    if ($tls) {
                        if (!$this->smtp->start_tls()) {
                            throw new \Exception($this->lang('connect_host'));
                        }
                        // We must resend EHLO after TLS negotiation
                        $this->smtp->hello($hello);
                    }
                    if ($this->smtp_auth) {
                        if (!$this->smtp->authenticate($this->username, $this->password, $this->auth_type)) {
                            throw new \Exception($this->lang('authenticate'));
                        }
                    }
                    return true;
                } catch (\Exception $exc) {
                    $lastexception = $exc;
                    $this->edebug($exc->getMessage());
                    // We must have connected, but then failed TLS or Auth, so close connection nicely
                    $this->smtp->quit();
                }
            }
        }
        // If we get here, all connection attempts have failed, so close connection hard
        $this->smtp->close();
        // As we've caught all exceptions, just report whatever the last one was
        if ($this->exceptions and !is_null($lastexception)) {
            throw $lastexception;
        }
        return false;
    }

    /**
     * Close the active SMTP session if one exists.
     * @return void
     */
    public function smtp_close() {
        if (is_a($this->smtp, 'SMTP')) {
            if ($this->smtp->connected()) {
                $this->smtp->quit();
                $this->smtp->close();
            }
        }
    }

    /**
     * Set the language for error messages.
     * Returns false if it cannot load the language file.
     * The default language is English.
     * @param string $langcode ISO 639-1 2-character language code (e.g. French is "fr")
     * @param string $lang_path Path to the language file directory, with trailing separator (slash)
     * @return boolean
     * @access public
     */
    public function set_language($langcode = 'en', $lang_path = '') {
        // Backwards compatibility for renamed language codes
        $renamed_langcodes = array('br' => 'pt_br', 'cz' => 'cs', 'dk' => 'da', 'no' => 'nb', 'se' => 'sv');
        if (isset($renamed_langcodes[$langcode])) {
            $langcode = $renamed_langcodes[$langcode];
        }
        // Define full set of translatable strings in English
        $PHPMAILER_LANG = array(
            'authenticate' => 'SMTP Error: Could not authenticate.',
            'connect_host' => 'SMTP Error: Could not connect to SMTP host.',
            'data_not_accepted' => 'SMTP Error: data not accepted.',
            'empty_message' => 'Message body empty',
            'encoding' => 'Unknown encoding: ',
            'execute' => 'Could not execute: ',
            'file_access' => 'Could not access file: ',
            'file_open' => 'File Error: Could not open file: ',
            'from_failed' => 'The following From address failed: ',
            'instantiate' => 'Could not instantiate mail function.',
            'invalid_address' => 'Invalid address: ',
            'mailer_not_supported' => ' mailer is not supported.',
            'provide_address' => 'You must provide at least one recipient email address.',
            'recipients_failed' => 'SMTP Error: The following recipients failed: ',
            'signing' => 'Signing Error: ',
            'smtp_connect_failed' => 'SMTP connect() failed.',
            'smtp_error' => 'SMTP server error: ',
            'variable_set' => 'Cannot set or reset variable: ',
            'extension_missing' => 'Extension missing: '
        );
        if (empty($lang_path)) {
            // Calculate an absolute path so it can work if CWD is not here
            $lang_path = dirname(__FILE__). DIRECTORY_SEPARATOR.'language'. DIRECTORY_SEPARATOR;
        }
        // Validate $langcode
        if (!preg_match('/^[a-z]{2}(?:_[a-zA-Z]{2})?$/', $langcode)) {
            $langcode = 'en';
        }
        $foundlang = true;
        $lang_file = $lang_path.'phpmailer.lang-'.$langcode.'.php';
        // There is no English translation file
        if ($langcode != 'en') {
            // Make sure language file path is readable
            if (!is_readable($lang_file)) {
                $foundlang = false;
            }
        }
        $this->language = $PHPMAILER_LANG;
        return (boolean)$foundlang; // Returns false if language not found
    }

    /**
     * Get the array of strings for the current language.
     * @return array
     */
    public function get_translations() {
        return $this->language;
    }

    /**
     * Create recipient headers.
     * @access public
     * @param string $type
     * @param array $addr An array of recipient,
     * where each recipient is a 2-element indexed array with element 0 containing an address
     * and element 1 containing a name, like:
     * array(array('joe@example.com', 'Joe User'), array('zoe@example.com', 'Zoe User'))
     * @return string
     */
    public function addr_append($type, $addr) {
        $addresses = array();
        foreach ($addr as $address) {
            $addresses[] = $this->addr_format($address);
        }
        return $type.': '.implode(', ', $addresses).$this->le;
    }

    /**
     * Format an address for use in a message header.
     * @access public
     * @param array $addr A 2-element indexed array, element 0 containing an address, element 1 containing a name
     *      like array('joe@example.com', 'Joe User')
     * @return string
     */
    public function addr_format($addr) {
        if (empty($addr[1])) { // No name provided
            return $this->secure_header($addr[0]);
        } else {
            return
                $this->encode_header($this->secure_header($addr[1]), 'phrase').' <'.$this->secure_header($addr[0]).'>';
        }
    }

    /**
     * Word-wrap message.
     * For use with mailers that do not automatically perform wrapping
     * and for quoted-printable encoded messages.
     * Original written by philippe.
     * @param string $message The message to wrap
     * @param integer $length The line length to wrap to
     * @param boolean $qp_mode Whether to run in Quoted-Printable mode
     * @access public
     * @return string
     */
    public function wrap_text($message, $length, $qp_mode = false) {
        if ($qp_mode) {
            $soft_break = sprintf(' =%s', $this->le);
        } else {
            $soft_break = $this->le;
        }
        // If utf-8 encoding is used, we will need to make sure we don't
        // split multibyte characters when we wrap
        $is_utf8 = (strtolower($this->char_set) == 'utf-8');
        $lelen = strlen($this->le);
        $crlflen = strlen(self::crlf);
        $message = $this->fix_eol($message);
        // Remove a trailing line break
        if (substr($message, -$lelen) == $this->le) {
            $message = substr($message, 0, -$lelen);
        }
        // Split message into lines
        $lines = explode($this->le, $message);
        // Message will be rebuilt in here
        $message = '';
        foreach ($lines as $line) {
            $words = explode(' ', $line);
            $buf = '';
            $firstword = true;
            foreach ($words as $word) {
                if ($qp_mode and (strlen($word) > $length)) {
                    $space_left = $length - strlen($buf) - $crlflen;
                    if (!$firstword) {
                        if ($space_left > 20) {
                            $len = $space_left;
                            if ($is_utf8) {
                                $len = $this->utf8_char_boundary($word, $len);
                            } elseif (substr($word, $len - 1, 1) == '=') {
                                $len--;
                            } elseif (substr($word, $len - 2, 1) == '=') {
                                $len -= 2;
                            }
                            $part = substr($word, 0, $len);
                            $word = substr($word, $len);
                            $buf .= ' '.$part;
                            $message .= $buf.sprintf('=%s', self::crlf);
                        } else {
                            $message .= $buf.$soft_break;
                        }
                        $buf = '';
                    }
                    while (strlen($word) > 0) {
                        if ($length <= 0) {
                            break;
                        }
                        $len = $length;
                        if ($is_utf8) {
                            $len = $this->utf8_char_boundary($word, $len);
                        } elseif (substr($word, $len - 1, 1) == '=') {
                            $len--;
                        } elseif (substr($word, $len - 2, 1) == '=') {
                            $len -= 2;
                        }
                        $part = substr($word, 0, $len);
                        $word = substr($word, $len);
                        if (strlen($word) > 0) {
                            $message .= $part.sprintf('=%s', self::crlf);
                        } else {
                            $buf = $part;
                        }
                    }
                } else {
                    $buf_o = $buf;
                    if (!$firstword) {
                        $buf .= ' ';
                    }
                    $buf .= $word;
                    if (strlen($buf) > $length and $buf_o != '') {
                        $message .= $buf_o.$soft_break;
                        $buf = $word;
                    }
                }
                $firstword = false;
            }
            $message .= $buf.self::crlf;
        }
        return $message;
    }

    /**
     * Find the last character boundary prior to $maxLength in a utf-8
     * quoted-printable encoded string.
     * Original written by Colin Brown.
     * @access public
     * @param string $encodedText utf-8 QP text
     * @param integer $maxLength Find the last character boundary prior to this length
     * @return integer
     */
    public function utf8_char_boundary($encodedText, $maxLength) {
        $foundSplitPos = false;
        $lookBack = 3;
        while (!$foundSplitPos) {
            $lastChunk = substr($encodedText, $maxLength - $lookBack, $lookBack);
            $encodedCharPos = strpos($lastChunk, '=');
            if (false !== $encodedCharPos) {
                // Found start of encoded character byte within $lookBack block.
                // Check the encoded byte value (the 2 chars after the '=')
                $hex = substr($encodedText, $maxLength - $lookBack + $encodedCharPos + 1, 2);
                $dec = hexdec($hex);
                if ($dec < 128) {
                    // Single byte character.
                    // If the encoded char was found at pos 0, it will fit
                    // otherwise reduce maxLength to start of the encoded char
                    if ($encodedCharPos > 0) {
                        $maxLength = $maxLength - ($lookBack - $encodedCharPos);
                    }
                    $foundSplitPos = true;
                } elseif ($dec >= 192) {
                    // First byte of a multi byte character
                    // Reduce maxLength to split at start of character
                    $maxLength = $maxLength - ($lookBack - $encodedCharPos);
                    $foundSplitPos = true;
                } elseif ($dec < 192) {
                    // Middle byte of a multi byte character, look further back
                    $lookBack += 3;
                }
            } else {
                // No encoded character found
                $foundSplitPos = true;
            }
        }
        return $maxLength;
    }

    /**
     * Apply word wrapping to the message body.
     * Wraps the message body to the number of chars set in the WordWrap property.
     * You should only do this to plain-text bodies as wrapping HTML tags may break them.
     * This is called automatically by createBody(), so you don't need to call it yourself.
     * @access public
     * @return void
     */
    public function set_word_wrap() {
        if ($this->word_wrap < 1) {
            return;
        }
        switch ($this->message_type) {
            case 'alt':
            case 'alt_inline':
            case 'alt_attach':
            case 'alt_inline_attach':
                $this->alt_body = $this->wrap_text($this->alt_body, $this->word_wrap);
                break;
            default:
                $this->body = $this->wrap_text($this->body, $this->word_wrap);
                break;
        }
    }

    /**
     * Assemble message headers.
     * @access public
     * @return string The assembled headers
     */
    public function create_header() {
        $result = '';
        $result .= $this->header_line('Date', $this->message_date == '' ? self::rfc_date() : $this->message_date);
        // To be created automatically by mail()
        if ($this->single_to) {
            if ($this->mailer != 'mail') {
                foreach ($this->to as $toaddr) {
                    $this->single_to_array[] = $this->addr_format($toaddr);
                }
            }
        } else {
            if (count($this->to) > 0) {
                if ($this->mailer != 'mail') {
                    $result .= $this->addr_append('To', $this->to);
                }
            } elseif (count($this->cc) == 0) {
                $result .= $this->header_line('To', 'undisclosed-recipients:;');
            }
        }
        $result .= $this->addr_append('From', array(array(trim($this->from), $this->from_name)));
        // Sendmail and mail() extract Cc from the header before sending
        if (count($this->cc) > 0) {
            $result .= $this->addr_append('Cc', $this->cc);
        }
        // Sendmail and mail() extract Bcc from the header before sending
        if (($this->mailer == 'sendmail' or $this->mailer == 'qmail' or $this->mailer == 'mail') and count($this->bcc) > 0) {
            $result .= $this->addr_append('Bcc', $this->bcc);
        }
        if (count($this->reply_to) > 0) {
            $result .= $this->addr_append('Reply-To', $this->reply_to);
        }
        // mail() sets the subject itself
        if ($this->mailer != 'mail') {
            $result .= $this->header_line('Subject', $this->encode_header($this->secure_header($this->subject)));
        }
        // Only allow a custom message ID if it conforms to RFC 5322 section 3.6.4
        // https://tools.ietf.org/html/rfc5322#section-3.6.4
        if ('' != $this->message_id and preg_match('/^<.*@.*>$/', $this->message_id)) {
            $this->last_message_id = $this->message_id;
        } else {
            $this->last_message_id = sprintf('<%s@%s>', $this->uniqueid, $this->server_hostname());
        }
        $result .= $this->header_line('Message-ID', $this->last_message_id);
        if (!is_null($this->priority)) {
            $result .= $this->header_line('X-Priority', $this->priority);
        }
        if ($this->xmailer == '') {
            $result .= $this->header_line(
                'X-Mailer',
                'PHPMailer '.$this->version.' (https://github.com/PHPMailer/PHPMailer)'
            );
        } else {
            $myXmailer = trim($this->xmailer);
            if ($myXmailer) {
                $result .= $this->header_line('X-Mailer', $myXmailer);
            }
        }
        if ($this->confirm_reading_to != '') {
            $result .= $this->header_line('Disposition-Notification-To', '<'.$this->confirm_reading_to.'>');
        }
        // Add custom headers
        foreach ($this->custom_header as $header) {
            $result .= $this->header_line(
                trim($header[0]),
                $this->encode_header(trim($header[1]))
            );
        }
        if (!$this->sign_key_file) {
            $result .= $this->header_line('MIME-Version', '1.0');
            $result .= $this->get_mail_mine();
        }
        return $result;
    }

    /**
     * Get the message MIME type headers.
     * @access public
     * @return string
     */
    public function get_mail_mine() {
        $result = '';
        $ismultipart = true;
        switch ($this->message_type) {
            case 'inline':
                $result .= $this->header_line('Content-Type', 'multipart/related;');
                $result .= $this->textLine("\tboundary=\"".$this->boundary[1].'"');
                break;
            case 'attach':
            case 'inline_attach':
            case 'alt_attach':
            case 'alt_inline_attach':
                $result .= $this->header_line('Content-Type', 'multipart/mixed;');
                $result .= $this->textLine("\tboundary=\"".$this->boundary[1].'"');
                break;
            case 'alt':
            case 'alt_inline':
                $result .= $this->header_line('Content-Type', 'multipart/alternative;');
                $result .= $this->textLine("\tboundary=\"".$this->boundary[1].'"');
                break;
            default:
                // Catches case 'plain': and case '':
                $result .= $this->textLine('Content-Type: '.$this->content_type.'; charset='.$this->char_set);
                $ismultipart = false;
                break;
        }
        // RFC1341 part 5 says 7bit is assumed if not specified
        if ($this->encoding != '7bit') {
            // RFC 2045 section 6.4 says multipart MIME parts may only use 7bit, 8bit or binary CTE
            if ($ismultipart) {
                if ($this->encoding == '8bit') {
                    $result .= $this->header_line('Content-Transfer-Encoding', '8bit');
                }
                // The only remaining alternatives are quoted-printable and base64, which are both 7bit compatible
            } else {
                $result .= $this->header_line('Content-Transfer-Encoding', $this->encoding);
            }
        }
        if ($this->mailer != 'mail') {
            $result .= $this->le;
        }
        return $result;
    }

    /**
     * Returns the whole MIME message.
     * Includes complete headers and body.
     * Only valid post preSend().
     * @see mailer::pre_send()
     * @access public
     * @return string
     */
    public function get_sent_mime_message() {
        return rtrim($this->mime_header.$this->mail_header, "\n\r").self::crlf.self::crlf.$this->mime_body;
    }

    /**
     * Create unique ID
     * @return string
     */
    protected function generate_id() {
        return md5(uniqid(time()));
    }

    /**
     * Assemble the message body.
     * Returns an empty string on failure.
     * @access public
     * @throws \Exception
     * @return string The assembled message body
     */
    public function create_body() {
        $body = '';
        // Create unique IDs and preset boundaries
        $this->uniqueid = $this->generate_id();
        $this->boundary[1] = 'b1_'.$this->uniqueid;
        $this->boundary[2] = 'b2_'.$this->uniqueid;
        $this->boundary[3] = 'b3_'.$this->uniqueid;
        if ($this->sign_key_file) {
            $body .= $this->get_mail_mine().$this->le;
        }
        $this->set_word_wrap();
        $bodyEncoding = $this->encoding;
        $bodyCharSet = $this->char_set;
        // Can we do a 7-bit downgrade?
        if ($bodyEncoding == '8bit' and !$this->has8bit_chars($this->body)) {
            $bodyEncoding = '7bit';
            //All ISO 8859, Windows codepage and UTF-8 charsets are ascii compatible up to 7-bit
            $bodyCharSet = 'us-ascii';
        }
        // If lines are too long, and we're not already using an encoding that will shorten them,
        // change to quoted-printable transfer encoding for the body part only
        if ('base64' != $this->encoding and self::has_line_longer_than_max($this->body)) {
            $bodyEncoding = 'quoted-printable';
        }
        $altBodyEncoding = $this->encoding;
        $altBodyCharSet = $this->char_set;
        // Can we do a 7-bit downgrade?
        if ($altBodyEncoding == '8bit' and !$this->has8bit_chars($this->alt_body)) {
            $altBodyEncoding = '7bit';
            // All ISO 8859, Windows codepage and UTF-8 charsets are ascii compatible up to 7-bit
            $altBodyCharSet = 'us-ascii';
        }
        // If lines are too long, and we're not already using an encoding that will shorten them,
        // change to quoted-printable transfer encoding for the alt body part only
        if ('base64' != $altBodyEncoding and self::has_line_longer_than_max($this->alt_body)) {
            $altBodyEncoding = 'quoted-printable';
        }
        // Use this as a preamble in all multipart message types
        $mimepre = "This is a multi-part message in MIME format.".$this->le.$this->le;
        switch ($this->message_type) {
            case 'inline':
                $body .= $mimepre;
                $body .= $this->get_boundary($this->boundary[1], $bodyCharSet, '', $bodyEncoding);
                $body .= $this->encode_string($this->body, $bodyEncoding);
                $body .= $this->le.$this->le;
                $body .= $this->attach_all('inline', $this->boundary[1]);
                break;
            case 'attach':
                $body .= $mimepre;
                $body .= $this->get_boundary($this->boundary[1], $bodyCharSet, '', $bodyEncoding);
                $body .= $this->encode_string($this->body, $bodyEncoding);
                $body .= $this->le.$this->le;
                $body .= $this->attach_all('attachment', $this->boundary[1]);
                break;
            case 'inline_attach':
                $body .= $mimepre;
                $body .= $this->textLine('--'.$this->boundary[1]);
                $body .= $this->header_line('Content-Type', 'multipart/related;');
                $body .= $this->textLine("\tboundary=\"".$this->boundary[2].'"');
                $body .= $this->le;
                $body .= $this->get_boundary($this->boundary[2], $bodyCharSet, '', $bodyEncoding);
                $body .= $this->encode_string($this->body, $bodyEncoding);
                $body .= $this->le.$this->le;
                $body .= $this->attach_all('inline', $this->boundary[2]);
                $body .= $this->le;
                $body .= $this->attach_all('attachment', $this->boundary[1]);
                break;
            case 'alt':
                $body .= $mimepre;
                $body .= $this->get_boundary($this->boundary[1], $altBodyCharSet, 'text/plain', $altBodyEncoding);
                $body .= $this->encode_string($this->alt_body, $altBodyEncoding);
                $body .= $this->le.$this->le;
                $body .= $this->get_boundary($this->boundary[1], $bodyCharSet, 'text/html', $bodyEncoding);
                $body .= $this->encode_string($this->body, $bodyEncoding);
                $body .= $this->le.$this->le;
                if (!empty($this->ical)) {
                    $body .= $this->get_boundary($this->boundary[1], '', 'text/calendar; method=REQUEST', '');
                    $body .= $this->encode_string($this->ical, $this->encoding);
                    $body .= $this->le.$this->le;
                }
                $body .= $this->end_boundary($this->boundary[1]);
                break;
            case 'alt_inline':
                $body .= $mimepre;
                $body .= $this->get_boundary($this->boundary[1], $altBodyCharSet, 'text/plain', $altBodyEncoding);
                $body .= $this->encode_string($this->alt_body, $altBodyEncoding);
                $body .= $this->le.$this->le;
                $body .= $this->textLine('--'.$this->boundary[1]);
                $body .= $this->header_line('Content-Type', 'multipart/related;');
                $body .= $this->textLine("\tboundary=\"".$this->boundary[2].'"');
                $body .= $this->le;
                $body .= $this->get_boundary($this->boundary[2], $bodyCharSet, 'text/html', $bodyEncoding);
                $body .= $this->encode_string($this->body, $bodyEncoding);
                $body .= $this->le.$this->le;
                $body .= $this->attach_all('inline', $this->boundary[2]);
                $body .= $this->le;
                $body .= $this->end_boundary($this->boundary[1]);
                break;
            case 'alt_attach':
                $body .= $mimepre;
                $body .= $this->textLine('--'.$this->boundary[1]);
                $body .= $this->header_line('Content-Type', 'multipart/alternative;');
                $body .= $this->textLine("\tboundary=\"".$this->boundary[2].'"');
                $body .= $this->le;
                $body .= $this->get_boundary($this->boundary[2], $altBodyCharSet, 'text/plain', $altBodyEncoding);
                $body .= $this->encode_string($this->alt_body, $altBodyEncoding);
                $body .= $this->le.$this->le;
                $body .= $this->get_boundary($this->boundary[2], $bodyCharSet, 'text/html', $bodyEncoding);
                $body .= $this->encode_string($this->body, $bodyEncoding);
                $body .= $this->le.$this->le;
                $body .= $this->end_boundary($this->boundary[2]);
                $body .= $this->le;
                $body .= $this->attach_all('attachment', $this->boundary[1]);
                break;
            case 'alt_inline_attach':
                $body .= $mimepre;
                $body .= $this->textLine('--'.$this->boundary[1]);
                $body .= $this->header_line('Content-Type', 'multipart/alternative;');
                $body .= $this->textLine("\tboundary=\"".$this->boundary[2].'"');
                $body .= $this->le;
                $body .= $this->get_boundary($this->boundary[2], $altBodyCharSet, 'text/plain', $altBodyEncoding);
                $body .= $this->encode_string($this->alt_body, $altBodyEncoding);
                $body .= $this->le.$this->le;
                $body .= $this->textLine('--'.$this->boundary[2]);
                $body .= $this->header_line('Content-Type', 'multipart/related;');
                $body .= $this->textLine("\tboundary=\"".$this->boundary[3].'"');
                $body .= $this->le;
                $body .= $this->get_boundary($this->boundary[3], $bodyCharSet, 'text/html', $bodyEncoding);
                $body .= $this->encode_string($this->body, $bodyEncoding);
                $body .= $this->le.$this->le;
                $body .= $this->attach_all('inline', $this->boundary[3]);
                $body .= $this->le;
                $body .= $this->end_boundary($this->boundary[2]);
                $body .= $this->le;
                $body .= $this->attach_all('attachment', $this->boundary[1]);
                break;
            default:
                // Catch case 'plain' and case '', applies to simple `text/plain` and `text/html` body content types
                // Reset the `Encoding` property in case we changed it for line length reasons
                $this->encoding = $bodyEncoding;
                $body .= $this->encode_string($this->body, $this->encoding);
                break;
        }
        if ($this->is_error()) {
            $body = '';
        } elseif ($this->sign_key_file) {
            try {
                if (!defined('PKCS7_TEXT')) {
                    throw new \Exception($this->lang('extension_missing').'openssl');
                }
                $file = tempnam(sys_get_temp_dir(), 'mail');
                if (false === file_put_contents($file, $body)) {
                    throw new \Exception($this->lang('signing').' Could not write temp file');
                }
                $signed = tempnam(sys_get_temp_dir(), 'signed');
                // Workaround for PHP bug https://bugs.php.net/bug.php?id=69197
                if (empty($this->sign_extracerts_file)) {
                    $sign = @openssl_pkcs7_sign(
                        $file, $signed, 'file://'.realpath($this->sign_cert_file),
                        array('file://'.realpath($this->sign_key_file), $this->sign_key_pass), null
                    );
                } else {
                    $sign = @openssl_pkcs7_sign(
                        $file, $signed, 'file://'.realpath($this->sign_cert_file),
                        array('file://'.realpath($this->sign_key_file), $this->sign_key_pass), null,
                        PKCS7_DETACHED, $this->sign_extracerts_file
                    );
                }
                if ($sign) {
                    @unlink($file);
                    $body = file_get_contents($signed);
                    @unlink($signed);
                    // The message returned by openssl contains both headers and body, so need to split them up
                    $parts = explode("\n\n", $body, 2);
                    $this->mime_header .= $parts[0].$this->le.$this->le;
                    $body = $parts[1];
                } else {
                    @unlink($file);
                    @unlink($signed);
                    throw new \Exception($this->lang('signing').openssl_error_string());
                }
            } catch (\Exception $exc) {
                $body = '';
                if ($this->exceptions) {
                    throw $exc;
                }
            }
        }
        return $body;
    }

    /**
     * Return the start of a message boundary.
     * @access protected
     * @param string $boundary
     * @param string $charSet
     * @param string $contentType
     * @param string $encoding
     * @return string
     */
    protected function get_boundary($boundary, $charSet, $contentType, $encoding) {
        $result = '';
        if ($charSet == '') {
            $charSet = $this->char_set;
        }
        if ($contentType == '') {
            $contentType = $this->content_type;
        }
        if ($encoding == '') {
            $encoding = $this->encoding;
        }
        $result .= $this->textLine('--'.$boundary);
        $result .= sprintf('Content-Type: %s; charset=%s', $contentType, $charSet);
        $result .= $this->le;
        // RFC1341 part 5 says 7bit is assumed if not specified
        if ($encoding != '7bit') {
            $result .= $this->header_line('Content-Transfer-Encoding', $encoding);
        }
        $result .= $this->le;
        return $result;
    }

    /**
     * Return the end of a message boundary.
     * @access protected
     * @param string $boundary
     * @return string
     */
    protected function end_boundary($boundary) {
        return $this->le.'--'.$boundary.'--'.$this->le;
    }

    /**
     * Set the message type.
     * PHPMailer only supports some preset message types, not arbitrary MIME structures.
     * @access protected
     * @return void
     */
    protected function set_message_type() {
        $type = array();
        if ($this->alternative_exists()) {
            $type[] = 'alt';
        }
        if ($this->inline_image_exists()) {
            $type[] = 'inline';
        }
        if ($this->attachment_exists()) {
            $type[] = 'attach';
        }
        $this->message_type = implode('_', $type);
        if ($this->message_type == '') {
            //The 'plain' message_type refers to the message having a single body element, not that it is plain-text
            $this->message_type = 'plain';
        }
    }

    /**
     * Format a header line.
     * @access public
     * @param string $name
     * @param string $value
     * @return string
     */
    public function header_line($name, $value) {
        return $name.': '.$value.$this->le;
    }

    /**
     * Return a formatted mail line.
     * @access public
     * @param string $value
     * @return string
     */
    public function textLine($value) {
        return $value.$this->le;
    }

    /**
     * Add an attachment from a path on the filesystem.
     * Never use a user-supplied path to a file!
     * Returns false if the file could not be found or read.
     * @param string $path Path to the attachment.
     * @param string $name Overrides the attachment name.
     * @param string $encoding File encoding (see $Encoding).
     * @param string $type File extension (MIME) type.
     * @param string $disposition Disposition to use
     * @throws \Exception
     * @return boolean
     */
    public function add_attachment($path, $name = '', $encoding = 'base64', $type = '', $disposition = 'attachment') {
        try {
            if (!@is_file($path)) {
                throw new \Exception($this->lang('file_access').$path, self::stop_continue);
            }
            // If a MIME type is not specified, try to work it out from the file name
            if ($type == '') {
                $type = self::filename_to_type($path);
            }
            $filename = basename($path);
            if ($name == '') {
                $name = $filename;
            }
            $this->attachment[] = array(
                0 => $path,
                1 => $filename,
                2 => $name,
                3 => $encoding,
                4 => $type,
                5 => false, // isStringAttachment
                6 => $disposition,
                7 => 0
            );
        } catch (\Exception $exc) {
            $this->set_error($exc->getMessage());
            $this->edebug($exc->getMessage());
            if ($this->exceptions) {
                throw $exc;
            }
            return false;
        }
        return true;
    }

    /**
     * Return the array of attachments.
     * @return array
     */
    public function get_attachments() {
        return $this->attachment;
    }

    /**
     * Attach all file, string, and binary attachments to the message.
     * Returns an empty string on failure.
     * @access protected
     * @param string $disposition_type
     * @param string $boundary
     * @return string
     */
    protected function attach_all($disposition_type, $boundary) {
        // Return text of body
        $mime = array();
        $cidUniq = array();
        $incl = array();
        // Add all attachments
        foreach ($this->attachment as $attachment) {
            // Check if it is a valid disposition_filter
            if ($attachment[6] == $disposition_type) {
                // Check for string attachment
                $string = '';
                $path = '';
                $bString = $attachment[5];
                if ($bString) {
                    $string = $attachment[0];
                } else {
                    $path = $attachment[0];
                }
                $inclhash = md5(serialize($attachment));
                if (in_array($inclhash, $incl)) {
                    continue;
                }
                $incl[] = $inclhash;
                $name = $attachment[2];
                $encoding = $attachment[3];
                $type = $attachment[4];
                $disposition = $attachment[6];
                $cid = $attachment[7];
                if ($disposition == 'inline' && array_key_exists($cid, $cidUniq)) {
                    continue;
                }
                $cidUniq[$cid] = true;
                $mime[] = sprintf('--%s%s', $boundary, $this->le);
                // Only include a filename property if we have one
                if (!empty($name)) {
                    $mime[] = sprintf(
                        'Content-Type: %s; name="%s"%s',
                        $type,
                        $this->encode_header($this->secure_header($name)),
                        $this->le
                    );
                } else {
                    $mime[] = sprintf(
                        'Content-Type: %s%s',
                        $type,
                        $this->le
                    );
                }
                // RFC1341 part 5 says 7bit is assumed if not specified
                if ($encoding != '7bit') {
                    $mime[] = sprintf('Content-Transfer-Encoding: %s%s', $encoding, $this->le);
                }
                if ($disposition == 'inline') {
                    $mime[] = sprintf('Content-ID: <%s>%s', $cid, $this->le);
                }
                // If a filename contains any of these chars, it should be quoted,
                // but not otherwise: RFC2183 & RFC2045 5.1
                // Fixes a warning in IETF's msglint MIME checker
                // Allow for bypassing the Content-Disposition header totally
                if (!(empty($disposition))) {
                    $encoded_name = $this->encode_header($this->secure_header($name));
                    if (preg_match('/[ \(\)<>@,;:\\"\/\[\]\?=]/', $encoded_name)) {
                        $mime[] = sprintf(
                            'Content-Disposition: %s; filename="%s"%s', $disposition, $encoded_name, $this->le.$this->le
                        );
                    } else {
                        if (!empty($encoded_name)) {
                            $mime[] = sprintf(
                                'Content-Disposition: %s; filename=%s%s', $disposition, $encoded_name, $this->le.$this->le
                            );
                        } else {
                            $mime[] = sprintf(
                                'Content-Disposition: %s%s', $disposition, $this->le.$this->le
                            );
                        }
                    }
                } else {
                    $mime[] = $this->le;
                }
                // Encode as string attachment
                if ($bString) {
                    $mime[] = $this->encode_string($string, $encoding);
                    if ($this->is_error()) {
                        return '';
                    }
                    $mime[] = $this->le.$this->le;
                } else {
                    $mime[] = $this->encode_file($path, $encoding);
                    if ($this->is_error()) {
                        return '';
                    }
                    $mime[] = $this->le.$this->le;
                }
            }
        }
        $mime[] = sprintf('--%s--%s', $boundary, $this->le);
        return implode('', $mime);
    }

    /**
     * Encode a file attachment in requested format.
     * Returns an empty string on failure.
     * @param string $path The full path to the file
     * @param string $encoding The encoding to use; one of 'base64', '7bit', '8bit', 'binary', 'quoted-printable'
     * @throws \Exception
     * @access protected
     * @return string
     */
    protected function encode_file($path, $encoding = 'base64') {
        try {
            if (!is_readable($path)) {
                throw new \Exception($this->lang('file_open').$path, self::stop_continue);
            }
            $magic_quotes = get_magic_quotes_runtime();
            if ($magic_quotes) {
							ini_set('magic_quotes_runtime', false);
            }
            $file_buffer = file_get_contents($path);
            $file_buffer = $this->encode_string($file_buffer, $encoding);
            if ($magic_quotes) {
							ini_set('magic_quotes_runtime', $magic_quotes);
            }
            return $file_buffer;
        } catch (\Exception $exc) {
            $this->set_error($exc->getMessage());
            return '';
        }
    }

    /**
     * Encode a string in requested format.
     * Returns an empty string on failure.
     * @param string $str The text to encode
     * @param string $encoding The encoding to use; one of 'base64', '7bit', '8bit', 'binary', 'quoted-printable'
     * @access public
     * @return string
     */
    public function encode_string($str, $encoding = 'base64') {
        $encoded = '';
        switch (strtolower($encoding)) {
            case 'base64':
                $encoded = chunk_split(base64_encode($str), 76, $this->le);
                break;
            case '7bit':
            case '8bit':
                $encoded = $this->fix_eol($str);
                // Make sure it ends with a line break
                if (substr($encoded, -(strlen($this->le))) != $this->le) {
                    $encoded .= $this->le;
                }
                break;
            case 'binary':
                $encoded = $str;
                break;
            case 'quoted-printable':
                $encoded = $this->encode_qp($str);
                break;
            default:
                $this->set_error($this->lang('encoding').$encoding);
                break;
        }
        return $encoded;
    }

    /**
     * Encode a header string optimally.
     * Picks shortest of Q, B, quoted-printable or none.
     * @access public
     * @param string $str
     * @param string $position
     * @return string
     */
    public function encode_header($str, $position = 'text') {
        $matchcount = 0;
        switch (strtolower($position)) {
            case 'phrase':
                if (!preg_match('/[\200-\377]/', $str)) {
                    // Can't use addslashes as we don't know the value of magic_quotes_sybase
                    $encoded = addcslashes($str, "\0..\37\177\\\"");
                    if (($str == $encoded) && !preg_match('/[^A-Za-z0-9!#$%&\'*+\/=?^_`{|}~ -]/', $str)) {
                        return ($encoded);
                    } else {
                        return ("\"$encoded\"");
                    }
                }
                $matchcount = preg_match_all('/[^\040\041\043-\133\135-\176]/', $str, $matches);
                break;
            /** @noinspection PhpMissingBreakStatementInspection */
            case 'comment':
                $matchcount = preg_match_all('/[()"]/', $str, $matches);
                // Intentional fall-through
            case 'text':
            default:
                $matchcount += preg_match_all('/[\000-\010\013\014\016-\037\177-\377]/', $str, $matches);
                break;
        }
        //There are no chars that need encoding
        if ($matchcount == 0) {
            return ($str);
        }
        $maxlen = 75 - 7 - strlen($this->char_set);
        // Try to select the encoding which should produce the shortest output
        if ($matchcount > strlen($str) / 3) {
            // More than a third of the content will need encoding, so B encoding will be most efficient
            $encoding = 'B';
            if (function_exists('mb_strlen') && $this->has_multi_bytes($str)) {
                // Use a custom function which correctly encodes and wraps long
                // multibyte strings without breaking lines within a character
                $encoded = $this->base64_encode_wrap_mb($str, "\n");
            } else {
                $encoded = base64_encode($str);
                $maxlen -= $maxlen % 4;
                $encoded = trim(chunk_split($encoded, $maxlen, "\n"));
            }
        } else {
            $encoding = 'Q';
            $encoded = $this->encode_q($str, $position);
            $encoded = $this->wrap_text($encoded, $maxlen, true);
            $encoded = str_replace('='.self::crlf, "\n", trim($encoded));
        }
        $encoded = preg_replace('/^(.*)$/m', ' =?'.$this->char_set."?$encoding?\\1?=", $encoded);
        $encoded = trim(str_replace("\n", $this->le, $encoded));
        return $encoded;
    }

    /**
     * Check if a string contains multi-byte characters.
     * @access public
     * @param string $str multi-byte text to wrap encode
     * @return boolean
     */
    public function has_multi_bytes($str) {
        if (function_exists('mb_strlen')) {
            return (strlen($str) > mb_strlen($str, $this->char_set));
        } else { // Assume no multibytes (we can't handle without mbstring functions anyway)
            return false;
        }
    }

    /**
     * Does a string contain any 8-bit chars (in any charset)?
     * @param string $text
     * @return boolean
     */
    public function has8bit_chars($text) {
        return (boolean)preg_match('/[\x80-\xFF]/', $text);
    }

    /**
     * Encode and wrap long multibyte strings for mail headers
     * without breaking lines within a character.
     * Adapted from a function by paravoid
     * @link http://www.php.net/manual/en/function.mb-encode-mimeheader.php#60283
     * @access public
     * @param string $str multi-byte text to wrap encode
     * @param string $linebreak string to use as linefeed/end-of-line
     * @return string
     */
    public function base64_encode_wrap_mb($str, $linebreak = null) {
        $start = '=?'.$this->char_set.'?B?';
        $end = '?='; $encoded = '';
        if ($linebreak === null) {
            $linebreak = $this->le;
        }
        $mb_length = mb_strlen($str, $this->char_set);
        // Each line must have length <= 75, including $start and $end
        $length = 75 - strlen($start) - strlen($end);
        // Average multi-byte ratio
        $ratio = $mb_length / strlen($str);
        // Base64 has a 4:3 ratio
        $avgLength = floor($length * $ratio * .75);
        for ($i = 0; $i < $mb_length; $i += $offset) {
            $lookBack = 0;
            do {
                $offset = $avgLength - $lookBack;
                $chunk = mb_substr($str, $i, $offset, $this->char_set);
                $chunk = base64_encode($chunk);
                $lookBack++;
            } while (strlen($chunk) > $length);
            $encoded .= $chunk.$linebreak;
        }
        // Chomp the last linefeed
        $encoded = substr($encoded, 0, -strlen($linebreak));
        return $encoded;
    }

    /**
     * Encode a string in quoted-printable format.
     * According to RFC2045 section 6.7.
     * @access public
     * @param string $string The text to encode
     * @param integer $line_max Number of chars allowed on a line before wrapping
     * @return string
     * @link http://www.php.net/manual/en/function.quoted-printable-decode.php#89417 Adapted from this comment
     */
    public function encode_qp($string, $line_max = 76) {
        // Use native function if it's available (>= PHP5.3)
        if (function_exists('quoted_printable_encode')) {
            return quoted_printable_encode($string);
        }
        // Fall back to a pure PHP implementation
        $string = str_replace(
            array('%20', '%0D%0A.', '%0D%0A', '%'), array(' ', "\r\n=2E", "\r\n", '='), rawurlencode($string)
        );
        return preg_replace('/[^\r\n]{'.($line_max - 3).'}[^=\r\n]{2}/', "$0=\r\n", $string);
    }

    /**
     * Backward compatibility wrapper for an old QP encoding function that was removed.
     * @see mailer::encode_qp()
     * @access public
     * @param string $string
     * @param integer $line_max
     * @param boolean $space_conv
     * @return string
     * @deprecated Use encodeQP instead.
     */
    public function encode_qp_php($string, $line_max = 76, $space_conv = false) {
        return $this->encode_qp($string, $line_max);
    }

    /**
     * Encode a string using Q encoding.
     * @link http://tools.ietf.org/html/rfc2047
     * @param string $str the text to encode
     * @param string $position Where the text is going to be used, see the RFC for what that means
     * @access public
     * @return string
     */
    public function encode_q($str, $position = 'text') {
        // There should not be any EOL in the string
        $pattern = '';
        $encoded = str_replace(array("\r", "\n"), '', $str);
        switch (strtolower($position)) {
            case 'phrase':
                // RFC 2047 section 5.3
                $pattern = '^A-Za-z0-9!*+\/ -';
                break;
            /** @noinspection PhpMissingBreakStatementInspection */
            case 'comment':
                // RFC 2047 section 5.2
                $pattern = '\(\)"';
                // intentional fall-through
                // for this reason we build the $pattern without including delimiters and []
            case 'text':
            default:
                // RFC 2047 section 5.1
                // Replace every high ascii, control, =, ? and _ characters
                $pattern = '\000-\011\013\014\016-\037\075\077\137\177-\377'.$pattern;
                break;
        }
        $matches = array();
        if (preg_match_all("/[{$pattern}]/", $encoded, $matches)) {
            // If the string contains an '=', make sure it's the first thing we replace
            // so as to avoid double-encoding
            $eqkey = array_search('=', $matches[0]);
            if (false !== $eqkey) {
                unset($matches[0][$eqkey]);
                array_unshift($matches[0], '=');
            }
            foreach (array_unique($matches[0]) as $char) {
                $encoded = str_replace($char, '='.sprintf('%02X', ord($char)), $encoded);
            }
        }
        // Replace every spaces to _ (more readable than =20)
        return str_replace(' ', '_', $encoded);
    }

    /**
     * Add a string or binary attachment (non-filesystem).
     * This method can be used to attach ascii or binary data,
     * such as a BLOB record from a database.
     * @param string $string String attachment data.
     * @param string $filename Name of the attachment.
     * @param string $encoding File encoding (see $Encoding).
     * @param string $type File extension (MIME) type.
     * @param string $disposition Disposition to use
     * @return void
     */
    public function add_string_attachment($string, $filename, $encoding = 'base64',
                                          $type = '', $disposition = 'attachment') {
        // If a MIME type is not specified, try to work it out from the file name
        if ($type == '') {
            $type = self::filename_to_type($filename);
        }
        // Append to $attachment array
        $this->attachment[] = array(
            0 => $string,
            1 => $filename,
            2 => basename($filename),
            3 => $encoding,
            4 => $type,
            5 => true, // isStringAttachment
            6 => $disposition,
            7 => 0
        );
    }

    /**
     * Add an embedded (inline) attachment from a file.
     * This can include images, sounds, and just about any other document type.
     * These differ from 'regular' attachments in that they are intended to be
     * displayed inline with the message, not just attached for download.
     * This is used in HTML messages that embed the images
     * the HTML refers to using the $cid value.
     * Never use a user-supplied path to a file!
     * @param string $path Path to the attachment.
     * @param string $cid Content ID of the attachment; Use this to reference
     *        the content when using an embedded image in HTML.
     * @param string $name Overrides the attachment name.
     * @param string $encoding File encoding (see $Encoding).
     * @param string $type File MIME type.
     * @param string $disposition Disposition to use
     * @return boolean True on successfully adding an attachment
     */
    public function add_embedded_image($path, $cid, $name = '', $encoding = 'base64',
                                       $type = '', $disposition = 'inline') {
        if (!@is_file($path)) {
            $this->set_error($this->lang('file_access').$path);
            return false;
        }
        // If a MIME type is not specified, try to work it out from the file name
        if ($type == '') {
            $type = self::filename_to_type($path);
        }
        $filename = basename($path);
        if ($name == '') {
            $name = $filename;
        }
        // Append to $attachment array
        $this->attachment[] = array(
            0 => $path,
            1 => $filename,
            2 => $name,
            3 => $encoding,
            4 => $type,
            5 => false, // isStringAttachment
            6 => $disposition,
            7 => $cid
        );
        return true;
    }

    /**
     * Add an embedded stringified attachment.
     * This can include images, sounds, and just about any other document type.
     * Be sure to set the $type to an image type for images:
     * JPEG images use 'image/jpeg', GIF uses 'image/gif', PNG uses 'image/png'.
     * @param string $string The attachment binary data.
     * @param string $cid Content ID of the attachment; Use this to reference
     *        the content when using an embedded image in HTML.
     * @param string $name
     * @param string $encoding File encoding (see $Encoding).
     * @param string $type MIME type.
     * @param string $disposition Disposition to use
     * @return boolean True on successfully adding an attachment
     */
    public function add_string_embedded_image($string, $cid, $name = '', $encoding = 'base64',
                                              $type = '', $disposition = 'inline') {
        // If a MIME type is not specified, try to work it out from the name
        if ($type == '' and !empty($name)) {
            $type = self::filename_to_type($name);
        }
        // Append to $attachment array
        $this->attachment[] = array(
            0 => $string,
            1 => $name,
            2 => $name,
            3 => $encoding,
            4 => $type,
            5 => true, // isStringAttachment
            6 => $disposition,
            7 => $cid
        );
        return true;
    }

    /**
     * Check if an inline attachment is present.
     * @access public
     * @return boolean
     */
    public function inline_image_exists() {
        foreach ($this->attachment as $attachment) {
            if ($attachment[6] == 'inline') {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if an attachment (non-inline) is present.
     * @return boolean
     */
    public function attachment_exists() {
        foreach ($this->attachment as $attachment) {
            if ($attachment[6] == 'attachment') {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if this message has an alternative body set.
     * @return boolean
     */
    public function alternative_exists() {
        return !empty($this->alt_body);
    }

    /**
     * Clear queued addresses of given kind.
     * @access protected
     * @param string $kind 'to', 'cc', or 'bcc'
     * @return void
     */
    public function clear_queued_addresses($kind) {
        $RecipientsQueue = $this->recipients_queue;
        foreach ($RecipientsQueue as $address => $params) {
            if ($params[0] == $kind) {
                unset($this->recipients_queue[$address]);
            }
        }
    }

    /**
     * Clear all To recipients.
     * @return void
     */
    public function clear_addresses() {
        foreach ($this->to as $to) {
            unset($this->all_recipients[strtolower($to[0])]);
        }
        $this->to = array();
        $this->clear_queued_addresses('to');
    }

    /**
     * Clear all CC recipients.
     * @return void
     */
    public function clear_ccs() {
        foreach ($this->cc as $cc) {
            unset($this->all_recipients[strtolower($cc[0])]);
        }
        $this->cc = array();
        $this->clear_queued_addresses('cc');
    }

    /**
     * Clear all BCC recipients.
     * @return void
     */
    public function clear_bccs() {
        foreach ($this->bcc as $bcc) {
            unset($this->all_recipients[strtolower($bcc[0])]);
        }
        $this->bcc = array();
        $this->clear_queued_addresses('bcc');
    }

    /**
     * Clear all ReplyTo recipients.
     * @return void
     */
    public function clear_reply_tos() {
        $this->reply_to = array();
        $this->reply_to_queue = array();
    }

    /**
     * Clear all recipient types.
     * @return void
     */
    public function clear_all_recipients() {
        $this->to = array();
        $this->cc = array();
        $this->bcc = array();
        $this->all_recipients = array();
        $this->recipients_queue = array();
    }

    /**
     * Clear all filesystem, string, and binary attachments.
     * @return void
     */
    public function clear_attachments() {
        $this->attachment = array();
    }

    /**
     * Clear all custom headers.
     * @return void
     */
    public function clear_custom_headers() {
        $this->custom_header = array();
    }

    /**
     * Add an error message to the error container.
     * @access protected
     * @param string $msg
     * @return void
     */
    protected function set_error($msg) {
        $this->error_count++;
        if ($this->mailer == 'smtp' and !is_null($this->smtp)) {
            $lasterror = $this->smtp->get_error();
            if (!empty($lasterror['error'])) {
                $msg .= $this->lang('smtp_error').$lasterror['error'];
                if (!empty($lasterror['detail'])) {
                    $msg .= ' Detail: '. $lasterror['detail'];
                }
                if (!empty($lasterror['smtp_code'])) {
                    $msg .= ' SMTP code: '.$lasterror['smtp_code'];
                }
                if (!empty($lasterror['smtp_code_ex'])) {
                    $msg .= ' Additional SMTP info: '.$lasterror['smtp_code_ex'];
                }
            }
        }
        $this->error_info = $msg;
    }

    /**
     * Return an RFC 822 formatted date.
     * @access public
     * @return string
     * @static
     */
    public static function rfc_date() {
        // Set the time zone to whatever the default is to avoid 500 errors
        // Will default to UTC if it's not set properly in php.ini
        date_default_timezone_set(@date_default_timezone_get());
        return date('D, j M Y H:i:s O');
    }

    /**
     * Get the server hostname.
     * Returns 'localhost.localdomain' if unknown.
     * @access protected
     * @return string
     */
    protected function server_hostname() {
        $result = 'localhost.localdomain';
        if (!empty($this->hostname)) {
            $result = $this->hostname;
        } elseif (isset($_SERVER) and array_key_exists('SERVER_NAME', $_SERVER) and !empty($_SERVER['SERVER_NAME'])) {
            $result = $_SERVER['SERVER_NAME'];
        } elseif (function_exists('gethostname') && gethostname() !== false) {
            $result = gethostname();
        } elseif (php_uname('n') !== false) {
            $result = php_uname('n');
        }
        return $result;
    }

    /**
     * Get an error message in the current language.
     * @access protected
     * @param string $key
     * @return string
     */
    protected function lang($key) {
        if (count($this->language) < 1) {
            $this->set_language('en'); // set the default language
        }
        if (array_key_exists($key, $this->language)) {
            if ($key == 'smtp_connect_failed') {
                // Include a link to troubleshooting docs on SMTP connection failure
                // this is by far the biggest cause of support questions
                // but it's usually not PHPMailer's fault.
                return $this->language[$key].' https://github.com/PHPMailer/PHPMailer/wiki/Troubleshooting';
            }
            return $this->language[$key];
        } else {
            // Return the key as a fallback
            return $key;
        }
    }

    /**
     * Check if an error occurred.
     * @access public
     * @return boolean True if an error did occur.
     */
    public function is_error() {
        return ($this->error_count > 0);
    }

    /**
     * Ensure consistent line endings in a string.
     * Changes every end of line from CRLF, CR or LF to $this->LE.
     * @access public
     * @param string $str String to fixEOL
     * @return string
     */
    public function fix_eol($str) {
        // Normalise to \n
        $nstr = str_replace(array("\r\n", "\r"), "\n", $str);
        // Now convert LE as needed
        if ($this->le !== "\n") {
            $nstr = str_replace("\n", $this->le, $nstr);
        }
        return $nstr;
    }

    /**
     * Add a custom header.
     * $name value can be overloaded to contain
     * both header name and value (name:value)
     * @access public
     * @param string $name Custom header name
     * @param string $value Header value
     * @return void
     */
    public function add_custom_header($name, $value = null) {
        if ($value === null) {
            // Value passed in as name:value
            $this->custom_header[] = explode(':', $name, 2);
        } else {
            $this->custom_header[] = array($name, $value);
        }
    }

    /**
     * Returns all custom headers.
     * @return array
     */
    public function get_custom_headers() {
        return $this->custom_header;
    }

    /**
     * Create a message body from an HTML string.
     * Automatically inlines images and creates a plain-text version by converting the HTML,
     * overwriting any existing values in Body and AltBody.
     * Do not source $message content from user input!
     * $basedir is prepended when handling relative URLs, e.g. <img src="/images/a.png"> and must not be empty
     * will look for an image file in $basedir/images/a.png and convert it to inline.
     * If you don't provide a $basedir, relative paths will be left untouched (and thus probably break in email)
     * If you don't want to apply these transformations to your HTML, just set Body and AltBody directly.
     * @access public
     * @param string $message HTML message string
     * @param string $basedir Absolute path to a base directory to prepend to relative paths to images
     * @param boolean|callable $advanced Whether to use the internal HTML to text converter
     *    or your own custom converter @see mailer::html_to_text()
     * @return string $message The transformed message Body
     */
    public function msg_html($message, $basedir = '', $advanced = false) {
        preg_match_all('/(src|background)=["\'](.*)["\']/Ui', $message, $images);
        if (array_key_exists(2, $images)) {
            if (strlen($basedir) > 1 && substr($basedir, -1) != '/') {
                // Ensure $basedir has a trailing /
                $basedir .= '/';
            }
            foreach ($images[2] as $imgindex => $url) {
                // Convert data URIs into embedded images
                if (preg_match('#^data:(image[^;,]*)(;base64)?,#', $url, $match)) {
                    $data = substr($url, strpos($url, ','));
                    if ($match[2]) {
                        $data = base64_decode($data);
                    } else {
                        $data = rawurldecode($data);
                    }
                    $cid = md5($url).'@phpmailer.0'; // RFC2392 S 2
                    if ($this->add_string_embedded_image($data, $cid, 'embed'.$imgindex, 'base64', $match[1])) {
                        $message = str_replace(
                            $images[0][$imgindex],
                            $images[1][$imgindex].'="cid:'.$cid.'"',
                            $message
                        );
                    }
                    continue;
                }
                if (
                    // Only process relative URLs if a basedir is provided (i.e. no absolute local paths)
                    !empty($basedir)
                    // Ignore URLs containing parent dir traversal (..)
                    && (strpos($url, '..') === false)
                    // Do not change urls that are already inline images
                    && substr($url, 0, 4) !== 'cid:'
                    // Do not change absolute URLs, including anonymous protocol
                    && !preg_match('#^[a-z][a-z0-9+.-]*:?//#i', $url)
                ) {
                    $filename = basename($url);
                    $directory = dirname($url);
                    if ($directory == '.') {
                        $directory = '';
                    }
                    $cid = md5($url).'@phpmailer.0'; // RFC2392 S 2
                    if (strlen($directory) > 1 && substr($directory, -1) != '/') {
                        $directory .= '/';
                    }
                    if ($this->add_embedded_image(
                        $basedir.$directory.$filename, $cid, $filename, 'base64',
                        self::mime_types((string)self::mb_pathinfo($filename, PATHINFO_EXTENSION))
                    )) {
                        $message = preg_replace(
                            '/'.$images[1][$imgindex].'=["\']'.preg_quote($url, '/').'["\']/Ui',
                            $images[1][$imgindex].'="cid:'.$cid.'"', $message
                        );
                    }
                }
            }
        }
        $this->is_html(true);
        // Convert all message body line breaks to CRLF, makes quoted-printable encoding work much better
        $this->body = $this->normalize_breaks($message);
        $this->alt_body = $this->normalize_breaks($this->html_to_text($message, $advanced));
        if (!$this->alternative_exists()) {
            $this->alt_body = 'To view this email message, open it in a program that understands HTML!'.
                self::crlf.self::crlf;
        }
        return $this->body;
    }

    /**
     * Convert an HTML string into plain text.
     * This is used by msgHTML().
     * Note - older versions of this function used a bundled advanced converter
     * which was been removed for license reasons in #232.
     * Example usage:
     * <code>
     * // Use default conversion
     * $plain = $mail->html2text($html);
     * // Use your own custom converter
     * $plain = $mail->html2text($html, function($html) {
     *     $converter = new MyHtml2text($html);
     *     return $converter->get_text();
     * });
     * </code>
     * @param string $html The HTML text to convert
     * @param boolean|callable $advanced Any boolean value to use the internal converter,
     *   or provide your own callable for custom conversion.
     * @return string
     */
    public function html_to_text($html, $advanced = false) {
        if (is_callable($advanced)) {
            return call_user_func($advanced, $html);
        }
        return html_entity_decode(
            trim(strip_tags(preg_replace('/<(head|title|style|script)[^>]*>.*?<\/\\1>/si', '', $html))),
            ENT_QUOTES, $this->char_set
        );
    }

    /**
     * Get the MIME type for a file extension.
     * @param string $ext File extension
     * @access public
     * @return string MIME type of file.
     * @static
     */
    public static function mime_types($ext = '') {
        $mimes = array(
            'xl'    => 'application/excel',
            'js'    => 'application/javascript',
            'hqx'   => 'application/mac-binhex40',
            'cpt'   => 'application/mac-compactpro',
            'bin'   => 'application/macbinary',
            'doc'   => 'application/msword',
            'word'  => 'application/msword',
            'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xltx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
            'potx'  => 'application/vnd.openxmlformats-officedocument.presentationml.template',
            'ppsx'  => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
            'pptx'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'sldx'  => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
            'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'dotx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
            'xlam'  => 'application/vnd.ms-excel.addin.macroEnabled.12',
            'xlsb'  => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
            'class' => 'application/octet-stream',
            'dll'   => 'application/octet-stream',
            'dms'   => 'application/octet-stream',
            'exe'   => 'application/octet-stream',
            'lha'   => 'application/octet-stream',
            'lzh'   => 'application/octet-stream',
            'psd'   => 'application/octet-stream',
            'sea'   => 'application/octet-stream',
            'so'    => 'application/octet-stream',
            'oda'   => 'application/oda',
            'pdf'   => 'application/pdf',
            'ai'    => 'application/postscript',
            'eps'   => 'application/postscript',
            'ps'    => 'application/postscript',
            'smi'   => 'application/smil',
            'smil'  => 'application/smil',
            'mif'   => 'application/vnd.mif',
            'xls'   => 'application/vnd.ms-excel',
            'ppt'   => 'application/vnd.ms-powerpoint',
            'wbxml' => 'application/vnd.wap.wbxml',
            'wmlc'  => 'application/vnd.wap.wmlc',
            'dcr'   => 'application/x-director',
            'dir'   => 'application/x-director',
            'dxr'   => 'application/x-director',
            'dvi'   => 'application/x-dvi',
            'gtar'  => 'application/x-gtar',
            'php3'  => 'application/x-httpd-php',
            'php4'  => 'application/x-httpd-php',
            'php'   => 'application/x-httpd-php',
            'phtml' => 'application/x-httpd-php',
            'phps'  => 'application/x-httpd-php-source',
            'swf'   => 'application/x-shockwave-flash',
            'sit'   => 'application/x-stuffit',
            'tar'   => 'application/x-tar',
            'tgz'   => 'application/x-tar',
            'xht'   => 'application/xhtml+xml',
            'xhtml' => 'application/xhtml+xml',
            'zip'   => 'application/zip',
            'mid'   => 'audio/midi',
            'midi'  => 'audio/midi',
            'mp2'   => 'audio/mpeg',
            'mp3'   => 'audio/mpeg',
            'mpga'  => 'audio/mpeg',
            'aif'   => 'audio/x-aiff',
            'aifc'  => 'audio/x-aiff',
            'aiff'  => 'audio/x-aiff',
            'ram'   => 'audio/x-pn-realaudio',
            'rm'    => 'audio/x-pn-realaudio',
            'rpm'   => 'audio/x-pn-realaudio-plugin',
            'ra'    => 'audio/x-realaudio',
            'wav'   => 'audio/x-wav',
            'bmp'   => 'image/bmp',
            'gif'   => 'image/gif',
            'jpeg'  => 'image/jpeg',
            'jpe'   => 'image/jpeg',
            'jpg'   => 'image/jpeg',
            'png'   => 'image/png',
            'tiff'  => 'image/tiff',
            'tif'   => 'image/tiff',
            'eml'   => 'message/rfc822',
            'css'   => 'text/css',
            'html'  => 'text/html',
            'htm'   => 'text/html',
            'shtml' => 'text/html',
            'log'   => 'text/plain',
            'text'  => 'text/plain',
            'txt'   => 'text/plain',
            'rtx'   => 'text/richtext',
            'rtf'   => 'text/rtf',
            'vcf'   => 'text/vcard',
            'vcard' => 'text/vcard',
            'xml'   => 'text/xml',
            'xsl'   => 'text/xml',
            'mpeg'  => 'video/mpeg',
            'mpe'   => 'video/mpeg',
            'mpg'   => 'video/mpeg',
            'mov'   => 'video/quicktime',
            'qt'    => 'video/quicktime',
            'rv'    => 'video/vnd.rn-realvideo',
            'avi'   => 'video/x-msvideo',
            'movie' => 'video/x-sgi-movie'
        );
        if (array_key_exists(strtolower($ext), $mimes)) {
            return $mimes[strtolower($ext)];
        }
        return 'application/octet-stream';
    }

    /**
     * Map a file name to a MIME type.
     * Defaults to 'application/octet-stream', i.e.. arbitrary binary data.
     * @param string $filename A file name or full path, does not need to exist as a file
     * @return string
     * @static
     */
    public static function filename_to_type($filename) {
        // In case the path is a URL, strip any query string before getting extension
        $qpos = strpos($filename, '?');
        if (false !== $qpos) {
            $filename = substr($filename, 0, $qpos);
        }
        $pathinfo = self::mb_pathinfo($filename);
        return self::mime_types($pathinfo['extension']);
    }

    /**
     * Multi-byte-safe pathinfo replacement.
     * Drop-in replacement for pathinfo(), but multibyte-safe, cross-platform-safe, old-version-safe.
     * Works similarly to the one in PHP >= 5.2.0
     * @link http://www.php.net/manual/en/function.pathinfo.php#107461
     * @param string $path A filename or path, does not need to exist as a file
     * @param integer|string $options Either a PATHINFO_* constant,
     *      or a string name to return only the specified piece, allows 'filename' to work on PHP < 5.2
     * @return string|array
     * @static
     */
    public static function mb_pathinfo($path, $options = null) {
        $ret = array('dirname' => '', 'basename' => '', 'extension' => '', 'filename' => '');
        $pathinfo = array();
        if (preg_match('%^(.*?)[\\\\/]*(([^/\\\\]*?)(\.([^\.\\\\/]+?)|))[\\\\/\.]*$%im', $path, $pathinfo)) {
            if (array_key_exists(1, $pathinfo)) {
                $ret['dirname'] = $pathinfo[1];
            }
            if (array_key_exists(2, $pathinfo)) {
                $ret['basename'] = $pathinfo[2];
            }
            if (array_key_exists(5, $pathinfo)) {
                $ret['extension'] = $pathinfo[5];
            }
            if (array_key_exists(3, $pathinfo)) {
                $ret['filename'] = $pathinfo[3];
            }
        }
        switch ($options) {
            case PATHINFO_DIRNAME:
            case 'dirname':
                return $ret['dirname'];
            case PATHINFO_BASENAME:
            case 'basename':
                return $ret['basename'];
            case PATHINFO_EXTENSION:
            case 'extension':
                return $ret['extension'];
            case PATHINFO_FILENAME:
            case 'filename':
                return $ret['filename'];
            default:
                return $ret;
        }
    }

    /**
     * Set or reset instance properties.
     * You should avoid this function - it's more verbose, less efficient, more error-prone and
     * harder to debug than setting properties directly.
     * Usage Example:
     * `$mail->set('SMTPSecure', 'tls');`
     *   is the same as:
     * `$mail->SMTPSecure = 'tls';`
     * @access public
     * @param string $name The property name to set
     * @param mixed $value The value to set the property to
     * @return boolean
     */
    public function set($name, $value = '') {
        if (property_exists($this, $name)) {
            $this->$name = $value;
            return true;
        } else {
            $this->set_error($this->lang('variable_set').$name);
            return false;
        }
    }

    /**
     * Strip newlines to prevent header injection.
     * @access public
     * @param string $str
     * @return string
     */
    public function secure_header($str) {
        return trim(str_replace(array("\r", "\n"), '', $str));
    }

    /**
     * Normalize line breaks in a string.
     * Converts UNIX LF, Mac CR and Windows CRLF line breaks into a single line break format.
     * Defaults to CRLF (for message bodies) and preserves consecutive breaks.
     * @param string $text
     * @param string $breaktype What kind of line break to use, defaults to CRLF
     * @return string
     * @access public
     * @static
     */
    public static function normalize_breaks($text, $breaktype = "\r\n") {
        return preg_replace('/(\r\n|\r|\n)/ms', $breaktype, $text);
    }

    /**
     * Set the public and private key files and password for S/MIME signing.
     * @access public
     * @param string $cert_filename
     * @param string $key_filename
     * @param string $key_pass Password for private key
     * @param string $extracerts_filename Optional path to chain certificate
     */
    public function sign($cert_filename, $key_filename, $key_pass, $extracerts_filename = '') {
        $this->sign_cert_file = $cert_filename;
        $this->sign_key_file = $key_filename;
        $this->sign_key_pass = $key_pass;
        $this->sign_extracerts_file = $extracerts_filename;
    }

    /**
     * Quoted-Printable-encode a DKIM header.
     * @access public
     * @param string $txt
     * @return string
     */
    public function dkim_qp($txt) {
        $line = '';
        for ($i = 0; $i < strlen($txt); $i++) {
            $ord = ord($txt[$i]);
            if (((0x21 <= $ord) && ($ord <= 0x3A)) || $ord == 0x3C || ((0x3E <= $ord) && ($ord <= 0x7E))) {
                $line .= $txt[$i];
            } else {
                $line .= '='.sprintf('%02X', $ord);
            }
        }
        return $line;
    }

    /**
     * Generate a DKIM signature.
     * @access public
     * @param string $signHeader
     * @throws \Exception
     * @return string The DKIM signature value
     */
    public function dkim_sign($signHeader) {
        if (!defined('PKCS7_TEXT')) {
            if ($this->exceptions) {
                throw new \Exception($this->lang('extension_missing').'openssl');
            }
            return '';
        }
        $privKeyStr = !empty($this->dkim_private_string)
            ? $this->dkim_private_string : file_get_contents($this->dkim_private);
        if ('' != $this->dkim_passphrase) {
            $privKey = openssl_pkey_get_private($privKeyStr, $this->dkim_passphrase);
        } else {
            $privKey = openssl_pkey_get_private($privKeyStr);
        }
        // Workaround for missing digest algorithms in old PHP & OpenSSL versions
        // @link http://stackoverflow.com/a/11117338/333340
        if (version_compare(PHP_VERSION, '5.3.0') >= 0 and
            in_array('sha256WithRSAEncryption', openssl_get_md_methods(true))) {
            if (openssl_sign($signHeader, $signature, $privKey, 'sha256WithRSAEncryption')) {
                openssl_pkey_free($privKey);
                return base64_encode($signature);
            }
        } else {
            $pinfo = openssl_pkey_get_details($privKey);
            $hash = hash('sha256', $signHeader);
            // 'Magic' constant for SHA256 from RFC3447
            // @link https://tools.ietf.org/html/rfc3447#page-43
            $t = '3031300d060960864801650304020105000420'.$hash;
            $pslen = $pinfo['bits'] / 8 - (strlen($t) / 2 + 3);
            $eb = pack('H*', '0001'.str_repeat('FF', $pslen).'00'.$t);
            if (openssl_private_encrypt($eb, $signature, $privKey, OPENSSL_NO_PADDING)) {
                openssl_pkey_free($privKey);
                return base64_encode($signature);
            }
        }
        openssl_pkey_free($privKey);
        return '';
    }

    /**
     * Generate a DKIM canonicalization header.
     * @access public
     * @param string $signHeader Header
     * @return string
     */
    public function dkim_header($signHeader) {
        $signHeader = preg_replace('/\r\n\s+/', ' ', $signHeader);
        $lines = explode("\r\n", $signHeader);
        foreach ($lines as $key => $line) {
            list($heading, $value) = explode(':', $line, 2);
            $heading = strtolower($heading);
            $value = preg_replace('/\s{2,}/', ' ', $value); // Compress useless spaces
            $lines[$key] = $heading.':'.trim($value); // Don't forget to remove WSP around the value
        }
        $signHeader = implode("\r\n", $lines);
        return $signHeader;
    }

    /**
     * Generate a DKIM canonicalization body.
     * @access public
     * @param string $body Message Body
     * @return string
     */
    public function dkim_body($body) {
        if ($body == '') {
            return "\r\n";
        }
        // Stabilize line endings
        $body = str_replace("\r\n", "\n", $body);
        $body = str_replace("\n", "\r\n", $body);
        // END stabilize line endings
        while (substr($body, strlen($body) - 4, 4) == "\r\n\r\n") {
            $body = substr($body, 0, strlen($body) - 2);
        }
        return $body;
    }

    /**
     * Create the DKIM header and body in a new message header.
     * @access public
     * @param string $headers_line Header lines
     * @param string $subject Subject
     * @param string $body Body
     * @return string
     */
    public function dkim_add($headers_line, $subject, $body) {
        $DKIMsignatureType = 'rsa-sha256'; // Signature & hash algorithms
        $DKIMcanonicalization = 'relaxed/simple'; // Canonicalization of header/body
        $DKIMquery = 'dns/txt'; // Query method
        $DKIMtime = time(); // Signature Timestamp = seconds since 00:00:00 - Jan 1, 1970 (UTC time zone)
        $subject_header = "Subject: $subject";
        $headers = explode($this->le, $headers_line);
        $from_header = ''; $to_header = ''; $date_header = ''; $current = '';
        foreach ($headers as $header) {
            if (strpos($header, 'From:') === 0) {
                $from_header = $header;
                $current = 'from_header';
            } elseif (strpos($header, 'To:') === 0) {
                $to_header = $header;
                $current = 'to_header';
            } elseif (strpos($header, 'Date:') === 0) {
                $date_header = $header;
                $current = 'date_header';
            } else {
                if (!empty($$current) && strpos($header, ' =?') === 0) {
                    $$current .= $header;
                } else {
                    $current = '';
                }
            }
        }
        $from = str_replace('|', '=7C', $this->dkim_qp($from_header));
        $to = str_replace('|', '=7C', $this->dkim_qp($to_header));
        $date = str_replace('|', '=7C', $this->dkim_qp($date_header));
        $subject = str_replace('|', '=7C', $this->dkim_qp($subject_header)); // Copied header fields (dkim-quoted-printable)
        $body = $this->dkim_body($body);
        $DKIMlen = strlen($body); // Length of body
        $DKIMb64 = base64_encode(pack('H*', hash('sha256', $body))); // Base64 of packed binary SHA-256 hash of body
        if ('' == $this->dkim_identity) {
            $ident = '';
        } else {
            $ident = ' i='.$this->dkim_identity.';';
        }
        $dkimhdrs = 'DKIM-Signature: v=1; a='.$DKIMsignatureType.'; q='.$DKIMquery.'; l='.$DKIMlen.'; s='.
            $this->dkim_selector.";\r\n"."\tt=".$DKIMtime.'; c='.$DKIMcanonicalization.";\r\n".
            "\th=From:To:Date:Subject;\r\n"."\td=".$this->dkim_domain.';'.$ident."\r\n".
            "\tz=$from\r\n"."\t|$to\r\n"."\t|$date\r\n"."\t|$subject;\r\n"."\tbh=".$DKIMb64.";\r\n"."\tb=";
        $toSign = $this->dkim_header(
            $from_header."\r\n".$to_header."\r\n".$date_header."\r\n".$subject_header."\r\n".$dkimhdrs
        );
        $signed = $this->dkim_sign($toSign);
        return $dkimhdrs.$signed."\r\n";
    }

    /**
     * Detect if a string contains a line longer than the maximum line length allowed.
     * @param string $str
     * @return boolean
     * @static
     */
    public static function has_line_longer_than_max($str) {
        // +2 to include CRLF line break for a 1000 total
        return (boolean)preg_match('/^(.{'.(self::max_line_length + 2).',})/m', $str);
    }

    /**
     * Allows for public read access to 'to' property.
     * @note: Before the send() call, queued addresses (i.e. with IDN) are not yet included.
     * @access public
     * @return array
     */
    public function get_to_addresses() {
        return $this->to;
    }

    /**
     * Allows for public read access to 'cc' property.
     * @note: Before the send() call, queued addresses (i.e. with IDN) are not yet included.
     * @access public
     * @return array
     */
    public function get_cc_addresses() {
        return $this->cc;
    }

    /**
     * Allows for public read access to 'bcc' property.
     * @note: Before the send() call, queued addresses (i.e. with IDN) are not yet included.
     * @access public
     * @return array
     */
    public function get_bcc_addresses() {
        return $this->bcc;
    }

    /**
     * Allows for public read access to 'ReplyTo' property.
     * @note: Before the send() call, queued addresses (i.e. with IDN) are not yet included.
     * @access public
     * @return array
     */
    public function get_reply_to_addresses() {
        return $this->reply_to;
    }

    /**
     * Allows for public read access to 'all_recipients' property.
     * @note: Before the send() call, queued addresses (i.e. with IDN) are not yet included.
     * @access public
     * @return array
     */
    public function get_all_recipient_addresses() {
        return $this->all_recipients;
    }

    /**
     * Perform a callback.
     * @param boolean $isSent
     * @param array $to
     * @param array $cc
     * @param array $bcc
     * @param string $subject
     * @param string $body
     * @param string $from
     */
    protected function do_callback($isSent, $to, $cc, $bcc, $subject, $body, $from) {
        if (!empty($this->action_function) && is_callable($this->action_function)) {
            $params = array($isSent, $to, $cc, $bcc, $subject, $body, $from);
            call_user_func_array($this->action_function, $params);
        }
    }

}