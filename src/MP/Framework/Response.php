<?php

namespace MP\Framework;

class Response {
	protected $proto   = 'HTTP/1.1';
	protected $ended   = false;
	protected $code    = 200;
	protected $body    = '';
	protected $headers = array();

	static $status_codes = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		426 => 'Upgrade Required',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates',
		507 => 'Insufficient Storage',
		509 => 'Bandwidth Limit Exceeded',
		510 => 'Not Extended'
	);

	public function __construct() {
	}

	public function isEnded()
	{
		return $this->ended;
	}

	public function flush()
	{
		$this->writeHead($this->code, $this->headers);
		echo $this->body;
	}

	public function end($body, $code = 200, $headers = array())
	{
		$this->ended = true;
		$this->code = $code;
		$this->headers = $headers;
		$this->body = $body;
	}

	public function json($obj, $code = 200, $headers = array())
	{
		$headers += array('Content-type' => 'application/json');
		$this->end(json_encode($obj), $code, $headers);
	}

	public function writeHead($status_code, $headers = array())
	{
		if (empty(self::$status_codes[$status_code])) {
			throw new \Exception("Unknown status code: $status_code");
		}

		// HTTP response code
		$status_string = $status_code . ' ' . self::$status_codes[$status_code];
		header($this->proto . ' ' . $status_string, true, $status_code);

		// Additional headers
		foreach($headers as $key => $header) {
			header($key . ': ' . $header);
		}
	}
}
