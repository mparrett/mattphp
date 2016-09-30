<?php

namespace MP\Framework;

/**
 * Represents the web application at the top level
 * @author Matt Parrett
 */
class App
{
    public $di;
    public $config;
    public $befores = array();

    public function __construct(&$di)
    {
        $this->di = $di;
    }

    /**
     * Get the application config with default values
     * Memoized
     */
    public function getConfig($key = null)
    {
        if (!$this->config) {
            $config = $this->di->get('app_config');

            $config = array_replace([
                    'default_template'          => 'sys/index.php',
                    'not_found_template'        => 'sys/404-not-found.php',
                    'app_controller_namespace'  => '\\',
                    'project_prefix'            => ''
                ],
                $config
            );

            $this->config = $config;
        }

        if ($key !== null) {
            return isset($this->config[$key]) ? $this->config[$key] : null;
        }

        return $this->config;
    }

    /**
     * Adds a "before" middleware to run before the request is processed
     */
    public function before($f)
    {
        $this->befores[] = $f;
    }

    /**
     * Route a callable or controller/method via regex
     */
    public function route($route, $f, $method = '*')
    {
        if ($method === '*') {
            $method = array('GET', 'POST', 'HEAD');
        } else {
            $method = (array)$method;
        }

        foreach ($method as $m) {
            $this->routes[$m][$route] = $f;
        }
    }

    /**
     * Matches a handler for a request by matching the route
     */
    public function getHandler($req)
    {
        if (!isset($this->routes[$req->getMethod()])) {
            return false;
        }

        $routes = $this->routes[$req->getMethod()];

        if (isset($routes[$req->getPath()])) {
            return $routes[$req->getPath()];
        }

        foreach ($routes as $pat => $f) {
            if (strpos($pat, '`') !== false && preg_match($pat, $req->getPath())) {
                return $f;
            }
        }
        return false;
    }

    // Add a "before" middleware that does basic HTTP auth
    public function requireAuth()
    {
        $di = $this->di;

        $this->before(function (&$req, $response) use (&$di) {
            // Basic auth middleware
            if (!isset($req->headers['Authorization'])) {
                return;
            }

            $auth = $req->headers['Authorization'];

            list($type, $hash) = explode(" ", $auth);

            if ($type != 'Basic') {
                return;
            }

            list($id, $pass) = explode(":", base64_decode($hash));

            $res = $di->get('db')->queryOne(
                "SELECT * FROM access_tokens WHERE `token` = '$id'"
            );

            if (!$res) {
                $response->json(['error' => true, 'message' => 'unauthorized'], 401);
            }
        });
    }

    public function handleDirectRoute($controller, $req, $response)
    {
        $exception = null;

        ob_start();

        try {
            $result = call_user_func_array($controller, array($req, $response));
        } catch (\Exception $exception) {
            $result = null;
        }

        $captured = ob_get_clean();

        if ($exception) {
            echo '<pre>';
            var_dump($exception);
            echo '</pre>';
            exit;
        }

        if ($response->isEnded()) {
            $response->flush();
            return;
        }

        $this->addDebugInfo($result, $captured, $exception);

        $this->render($response, $this->getConfig('default_template'), $result, $req);
        $response->flush();
    }

    /**
     * Get a CakePHP-style automatic route
     */
    public function getAutoRoute($req)
    {
        // Default to index controller
        $controller_name = $req->path_args[0] ? $req->path_args[0] : 'index';

        // Default to 'index' action or use /controller/action
        $method = count($req->path_args) > 1 ? $req->path_args[1] : 'index';

        return array($controller_name, $method);
    }

    /**
     * Process and flush the request/response pair
     */
    public function handle(&$req, &$response = null)
    {
        // Run "before" middlewares
        foreach ($this->befores as $before) {
            $before($req, $response);
        }

        if ($response->isEnded()) {
            return $response->flush();
        }

        // Now check direct route controllers
        $handler = $this->getHandler($req);

        if ($handler) {
            if (!is_string($handler)) {
                return $this->handleDirectRoute($handler, $req, $response);
            } else {
                list($controller_name, $method) = explode('::', $handler);
            }
        } else {
            // Automagical controller/method mappings
            list($controller_name, $method) = $this->getAutoRoute($req);
        }

        // Handle controller not found
        $controller = $this->getController($controller_name);
        if ($controller === false) {
            $this->render($response, $this->getConfig('not_found_template'), null, $req);
            $response->flush();
            return;
        }

        // Process request
        $res = $this->process($controller, $method, $req, $response);

        if ($res === null) {
            return;
        }

        // Response was not flushed, continue with automagical rendering

        list($result, $captured, $exception) = $res;

        // Don't try rendering a template if content type was application/json

        if ($req->getContentType() == 'application/json') {
            if ($exception) {
                $send['exception'] = $exception->getMessage();
                $send['captured'] = $captured;

                $response->json($send);
            } elseif ($captured) {
                $send['captured'] = $captured;

                $response->json($send);
            } else {
                $response->json($result);
            }
            $response->flush();

            return;
        }

        // Default template convention
        // PROJECT_PREFIX/CONTROLLER/METHOD.php

        $template = $controller_name.'/'.$method.'.php';

        if ($this->getConfig('project_prefix') != '') {
            $template = $this->getConfig('project_prefix').'/'.$template;
        }

        // Controllers can override the template
        if (!empty($controller->template)) {
            $template = $controller->template;
        }

        $this->addDebugInfo($result, $captured, $exception);

        // Raw render and flush
        //$response->end($this->di->get('templates')->render($template, $result));
        //$response->flush()

        // Render and flush, catching exceptions
        $resp = $this->render($response, $template, $result, $req);
        $resp->flush();
    }

    /**
     * Factory for Controller, by controller name
     */
    public function getController($controller_name)
    {
        $controller_class = $this->getConfig('app_controller_namespace').
            '\\'.ucfirst(str_replace('-', ' ', $controller_name)).'Controller';

        // Controller not found
        if (!class_exists($controller_class)) {
            return false;
        }

        return new $controller_class();
    }

    /**
     * Process a request with a specific controller/method combo
     * Returns null if response was rendered/flushed
     * This can happen if the method was not found
     * Or if the controller had a custom response/override
     */
    public function process($controller, $method, $req, $response)
    {
        // Method not found
        if (!method_exists($controller, $method)) {
            $this->render($response, $this->getConfig('not_found_template'), null, $req);
            $response->flush();
            return;
        }

        // Initialize it
        if (method_exists($controller, '_init')) {
            $controller->_init($req, $response, $this->di);
        }

        // Now execute the controller.
        // Save result, so it can be extracted when views are rendered
        // Also capture any spurious output from the action
        // Can be used for debugging

        $exception = null;
        ob_start();
        try {
            if (!empty($controller->simple) && $controller->simple) {
                // Call Controller->method(arg1, arg2, ...);

                // XXX: Untested, may remove
                // XXX: Hack for CakePHP-style path argument injection
                // Would be better to use reflection or some such to verify number of arguments
                // Note: Modern PHP supports variadic arguments
                //$func_args = array_slice($req->path_args, 1);
                //$func_args = array_merge($func_args, array(null, null, null, null, null));

                $result = call_user_func_array(
                    array($controller, $method), array_slice($req->path_args, 2)
                );
            } else {
                $result = call_user_func_array(
                    array($controller, $method), array($req, $response)
                );
            }
        } catch (\Exception $exception) {
            $result = array();
        }
        $captured = ob_get_clean();

        // Controller may have ended the response
        if ($response->isEnded()) {
            $response->flush();
            return;
        }

        return array($result, $captured, $exception);
    }

    /**
     * Attempt to render a template for a given response
     * If the template is not found or there is an exception,
     * display something nice
     */
    public function render(&$response, $template, $vars, $req, $code = 200)
    {
        $templates = $this->di->get('templates');

        if (!$templates->exists($template)) {
            $vars['_nonexist_template'] = $template;
            $template = 'sys/template-not-found.php';
            $code = 500;
        }

        $vars['_template'] = $template;
        $vars['_req'] = $req;

        // A template could throw an exception
        try {
            $out = $templates->render($template, $vars);
        } catch (\Exception $e) {
            $vars['_exception'] = $e;
            $out = $templates->render('sys/rendering-exception.php', $vars);
            $code = 500;
        }

        $response->end($out, $code);
        return $response;
    }

    /**
     * Add some debug info to a result, for displaying in the dev template
     */
    public function addDebugInfo(&$result, $captured, $exception)
    {
        if ($result === null) {
            $result = array();
        }

        // DEV/DEBUG

        //$captured .= '<h4>Included Files:</h4>'.join("\n", get_included_files());

        if ($captured != null && strlen($captured) > 0 && $result !== null) {
            $result = array_merge($result, array('_captured'=>$captured));
        }

        if ($exception !== null && $result !== null) {
            $result = array_merge($result, array('_exception'=>$exception));
        }
    }

    /**
     * Register error handling (dev only)
     */
    public function registerCustomErrorHandler()
    {
        $err_handler = new \MP\Framework\ErrorHandler();

        error_reporting(E_ALL | E_STRICT);
        //error_reporting(E_ALL);
        set_error_handler(array($err_handler, 'handler'));
        register_shutdown_function(array($err_handler, 'shutdown'));
    }
}
