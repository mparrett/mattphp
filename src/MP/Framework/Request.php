<?php

namespace MP\Framework;

////
// Simple Request
////

class Request {
	var $path;
	var $path_args = array();

	var $query;
	var $query_args = array();

	var $fragment;
	var $method = 'GET';

	var $get;
	var $post;
	var $request;

	public function __construct($uri = null)
	{
		if ($uri !== null)
			$this->initializeFromURI($uri);
	}

	public function getPath()
	{
		return $this->path;
	}

	public function getQuery()
	{
		return $this->query;
	}

	public function getMethod()
	{
		return $this->method;
	}

	public function initializeFromURI($uri)
	{
		$parsed = parse_url($uri);

		if (isset($parsed['query'])) {
			$this->query    = $parsed['query'];

			// More or less mimics $_GET
			// when constructed from a raw URI
			// automatic urldecoding
			parse_str($this->query, $this->query_args);
		}

		if (isset($parsed['fragment']))
			$this->fragment = $parsed['fragment'];

		$this->path     = $parsed['path'];
		$this->path_args = explode('/', trim($this->path, '/'));
	}

	public static function createFromURI($uri)
	{
		$req = new Request();
		$req->initializeByURI($uri);

		return $req;
	}

	// THis is the preferred method of initialization
	public static function createFromGlobals()
	{
		$req = new Request();

		$req->query_args 	= $_GET;
		$req->post 			= $_POST;
		$req->cookie 		= $_COOKIE;
		$req->request 		= $_REQUEST;

		if (isset($_SERVER['REQUEST_METHOD']))
			$req->method 	= $_SERVER['REQUEST_METHOD'];

		$req->headers 		= $req->getallheaders();

		// XXX: This might be non-standard in some way
		$req->initializeFromURI($_SERVER['REQUEST_URI']);

		return $req;
	}

	function getContent()
	{
		return file_get_contents("php://input");
	}

	function getContentType()
	{
		if (isset($this->headers['Content-Type']))
			return $this->headers['Content-Type'];
	}

	function getallheaders()
	{
		if (function_exists('getallheaders'))
		return getallheaders();

		$headers = '';
		foreach ($_SERVER as $k => $v) {
			if (substr($name, 0, 5) == 'HTTP_') {
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($k, 5)))))] = $v;
			} else if ($name == "CONTENT_TYPE") {
				$headers["Content-Type"] = $v;
			} else if ($name == "CONTENT_LENGTH") {
				$headers["Content-Length"] = $v;
			}
		}

		return $headers;
	}

	function replace($arr)
	{
		$this->request = array_replace($this->request, $arr);
	}
}
