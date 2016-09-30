<?php

namespace MP\Framework;

/**
 * Matt PHP
 * Models a web request
 * @author Matt Parrett
 */
class Request
{
    public $path;
    public $path_args = array();

    public $query;
    public $query_args = array();

    public $fragment;
    public $method = 'GET';

    public $get;
    public $post;
    public $request;

    public function __construct($uri = null)
    {
        if ($uri !== null) {
            $this->initializeFromURI($uri);
        }
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

    /**
     * Initializes the request from a URI string
     */
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

        if (isset($parsed['fragment'])) {
            $this->fragment = $parsed['fragment'];
        }

        $this->path     = $parsed['path'];
        $this->path_args = explode('/', trim($this->path, '/'));
    }

    public static function createFromURI($uri)
    {
        $req = new Request();
        $req->initializeByURI($uri);

        return $req;
    }

    /**
     * Initialize state from server globals
     * Preferred method of initialization
     */
    public static function createFromGlobals()
    {
        $req = new Request();

        $req->query_args     = $_GET;
        $req->post           = $_POST;
        $req->cookie         = $_COOKIE;
        $req->request        = $_REQUEST;

        if (isset($_SERVER['REQUEST_METHOD'])) {
            $req->method    = $_SERVER['REQUEST_METHOD'];
        }

        $req->headers        = $req->getallheaders();

        // XXX: This might be non-standard in some way
        $req->initializeFromURI($_SERVER['REQUEST_URI']);

        return $req;
    }

    /**
     * Gets raw request content
     */
    public function getContent()
    {
        return file_get_contents("php://input");
    }

    public function getContentType()
    {
        if (isset($this->headers['Content-Type'])) {
            return $this->headers['Content-Type'];
        }
    }

    /**
     * Provides getallheaders function which may be missing
     */
    public function getallheaders()
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        $headers = '';
        foreach ($_SERVER as $k => $v) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($k, 5)))))] = $v;
            } elseif ($name == "CONTENT_TYPE") {
                $headers["Content-Type"] = $v;
            } elseif ($name == "CONTENT_LENGTH") {
                $headers["Content-Length"] = $v;
            }
        }

        return $headers;
    }

    public function replace($arr)
    {
        $this->request = array_replace($this->request, $arr);
    }
}
